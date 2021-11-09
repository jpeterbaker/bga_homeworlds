<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * binaryHomeworlds implementation : © <Your name here> <Your email address here>
  *
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  *
  * binaryhomeworlds.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */

require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class binaryHomeworlds extends Table {
    function __construct( ) {
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();

        self::initGameStateLabels( array( 
            'used_free'         => 11,
            'sacrifice_color'   => 12,
            'sacrifice_actions' => 13
        ));
    }

    protected function getGameName( ) {
        // Used for translations and stuff. Please do not modify.
        return 'binaryhomeworlds';
    }

    /*
    setupNewGame:

    This method is called only once, when a new game is launched.
    In this method, you must setup the game according to the game rules, so that
    the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() ) {
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        // Create players
        // Note: if you added some extra field on 'player' table in the database (dbmodel.sql), you can initialize it there.
        $sql = 'INSERT INTO player (
            player_id,
            player_color,
            player_canal,
            player_name,
            player_avatar
        ) VALUES ';

        $values = array();
        foreach($players as $player_id => $player) {
            $color = array_shift( $default_colors );
            $values[] = "(
                '".$player_id."',
                '$color',
                '".$player['player_canal']."',
                '".addslashes( $player['player_name'] )."',
                '".addslashes( $player['player_avatar'] )."'
            )";
        }
        $sql .= implode($values,',');
        self::DbQuery($sql);
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        $sql = 'UPDATE player
            SET player_score=100';
        self::DbQuery($sql);

        /////////////////////////////////////////////////
        // Build up one big SQL command for all pieces //
        /////////////////////////////////////////////////
        $sql = 'INSERT INTO Pieces (color,pips) VALUES (';
        for($color=1;$color<=4;$color++){
            for($pips=1;$pips<=3;$pips++){
                for($i=1;$i<=3;$i++){
                    // Three pieces in each of the three sizes and four colors
                    $sql .= $color . ',' . $pips . '),(';
                }
            }
        }
        // Remove the final ',('
        $sql=substr($sql,0,-2);
        self::DbQuery($sql);

/*
        // Put a few pieces on the board to see if they're displayed properly
        [$system_id,$autogen] = $this->make_system('Home of Babamots',$player_id);

        $this->make_star(1,$system_id);
        $this->make_star(31,$system_id);
        $this->make_ship(25,$player_id,$system_id);
        $this->make_ship(2,$player_id,$system_id);
        $this->make_ship(10,$player_id,$system_id);

        $this->make_ship(3,666,$system_id);
        $this->make_ship(7,666,$system_id);
//*/

        // Change used_free to 1 when free move has been used
        // (This flag is needed in after_cat state to determine
        // whether to transition to want_free or want_catastrophe
        // if there has been no sacrifice)
        self::setGameStateInitialValue('used_free'  ,0);
        // Color zero indicates no sacrifice has occurred
        self::setGameStateInitialValue('sacrifice_color'  ,0);
        self::setGameStateInitialValue('sacrifice_actions',0);
        /************ End of the game initialization *****/
        self::activeNextPlayer();
    }

    /*
    getAllDatas:

    Gather all informations about current game situation (visible by the current player).

    The method is called each time the game interface is displayed to a player, ie:
    _ when the game starts
    _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas() {
        $result = array();

        // !! We must only return informations visible by this player !!
        // The first column of the SELECT command will be the key of the array
        // returned by getCollectionFromDb
        // $current_player_id = self::getCurrentPlayerId();

        // Get information about players
        $sql = "SELECT
            player_id,
            player_score,
            player_no
        FROM player";
        $result['players'] = self::getCollectionFromDb($sql);

        // Get bank pieces
        $sql = "SELECT piece_id,color,pips
            FROM Pieces
            WHERE system_id IS NULL";
        $result['bank'] = self::getCollectionFromDb($sql);

        ////////////////////////////////////////
        // Get info on all the in-play pieces //
        ////////////////////////////////////////
        $sql = "SELECT system_id,system_name,homeplayer_id
            FROM Systems";
        $systems = self::getCollectionFromDb($sql);

        $sql = "SELECT piece_id,color,pips,system_id
            FROM Pieces
            WHERE (system_id IS NOT NULL) AND (owner_id IS NULL)";
        $stars = self::getCollectionFromDb($sql);

        $sql = "SELECT piece_id,color,pips,system_id,owner_id
            FROM Pieces
            WHERE owner_id IS NOT NULL";
        $ships = self::getCollectionFromDb($sql);

        //////////////////////////////
        // Set up system structures //
        //////////////////////////////
        $result['systems'] = &$systems;

        // Add stars to systems
        foreach($stars as $piece_id => $row){
            // The amperstand makes it a reference that can be changed
            $system_id = $row['system_id'];
            $system = &$systems[$system_id];
            $system['stars'][$piece_id] = $row;
        }

        // Add ships to systems
        foreach($ships as $piece_id => $row){
            $system_id = $row['system_id'];
            $system = &$systems[$system_id];
            $system['ships'][$piece_id] = $row;
        }

        return $result;
    }

    /*
    getGameProgression:

    Compute and return the current game progression.
    The number returned must be an integer beween 0 (=the game just started) and
    100 (= the game is finished or almost finished).

    This method is called each time we are in a game state
    with the "updateGameProgression" property set to true
    (see states.inc.php)
    */
    function getGameProgression() {
        // TODO: compute and return the game progression
        return 0;
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    // Send a debug notification
    function say($s){
        self::notifyAllPlayers('notif_debug',$s,[]);
    }

    function array_key_first($x){
        foreach($x as $key=>$value)
            return $key;
        return NULL;
    }

    function make_system($system_name=NULL,$homeplayer_id=NULL){
        if(is_null($homeplayer_id)){
            $sql = "INSERT
                INTO Systems (system_name)
                VALUES ('".$system_name."')";
        }
        else{
            $sql = "INSERT
                INTO Systems (system_name,homeplayer_id)
                VALUES ('".$system_name."',".$homeplayer_id.")";
        }
        self::DbQuery($sql);

        $sql = 'SELECT MAX(system_id) FROM Systems';
        $system_id = $this->array_key_first(self::getCollectionFromDb($sql));
        if(is_null($system_name)){
            $idx = ($system_id-2) % $this->name_count;
            $system_name = $this->system_names[$idx];
            // Add a number if this system name has been used before
            if($system_id-2 >= $this->name_count)
                $system_name .= ' '.(1+intdiv($system_id-2,$this->name_count));
            $sql = "UPDATE Systems
                SET system_name='".$system_name."'
                WHERE system_id=".$system_id;
            self::DbQuery($sql);
        }
        return [$system_id,$system_name];
    }

    // Raise a user-visible exception if piece is not in bank
    function ensure_in_bank($piece_id){
        $sql = 'SELECT piece_id FROM Pieces
            WHERE piece_id='.$piece_id.'
                AND system_id IS NOT NULL';
        $result = self::getCollectionFromDb($sql);
        if(count($result)!=0){
            // Use BgaVisibleSystemException for code that should only be reachable by cheaters
            // Use BgaUserException for problems that could be the result of innocent user error
            throw new BgaVisibleSystemException(
                self::_('No such piece in the bank.')
            );
        }
    }

    function get_all_overpopulations(){
        // The first SELECT column must be unique.
        // For a single unique column representing color and count,
        // put system_id as the tens digit and count as the ones digit.
        // (There are only 9 pieces of each color)
        // TODO adapt for more players
        $sql = 'SELECT color+10*system_id,COUNT(piece_id),system_id,color
            FROM Pieces
            WHERE system_id IS NOT NULL
            GROUP BY system_id,color
            HAVING COUNT(piece_id)>=4';
        $result = self::getCollectionFromDb($sql);
        return $result;
    }

	function exist_overpopulations(){
        return count($this->get_all_overpopulations()) > 0;
    }

    // Put the piece with the given id into the bank
    function put_in_bank($piece_ids){
        if(!is_array($piece_ids)){
            $sql = 'UPDATE Pieces
                SET system_id=NULL,
                    owner_id=NULL
                WHERE piece_id='.$piece_ids;
            self::DbQuery($sql);
            return;
        }
        if(count($piece_ids) == 0)
            return;
        // Construct a query to return all the pieces
        $sql = 'UPDATE Pieces
            SET system_id=NULL,
                owner_id=NULL
            WHERE ';
        foreach($piece_ids as $piece_id)
            $sql .= 'piece_id='.$piece_id.' OR ';
        // Remove final ' OR '
        $sql=substr($sql,0,-4);
        self::DbQuery($sql);
    }

    // Put the piece with the given id into the system with the given id
    // as a star
    // Throws an error if the piece is not in the bank
    function make_star($piece_id,$system_id){
        $this->ensure_in_bank($piece_id);
        $sql = 'UPDATE Pieces
            SET system_id='.$system_id.',
                owner_id=NULL
            WHERE piece_id='.$piece_id;
        self::DbQuery($sql);
    }

    // Put the piece with the given id into the system with the given id
    // as a ship with the given owner
    // Throws an error if the piece is not in the bank
    function make_ship($piece_id,$owner,$system_id){
        $this->ensure_in_bank($piece_id);
        $sql = 'UPDATE Pieces
            SET system_id='.$system_id.',
                owner_id='.$owner.'
            WHERE piece_id='.$piece_id;
        self::DbQuery($sql);
    }

    // Returns the id of any one piece of the given color and size from the bank.
    // Returns NULL if there are none.
    function get_id_from_bank($color,$pips){
        $sql = 'SELECT piece_id FROM Pieces
            WHERE color='.$color.'
                AND pips='.$pips.'
                AND system_id IS NULL
            LIMIT 1';
        $result = self::getCollectionFromDb($sql);
        return $this->array_key_first($result);
    }

    // Returns system_id of the home of this player
    function get_home($player_id){
        $sql = 'SELECT system_id FROM Systems
            WHERE homeplayer_id='.$player_id;
        $result = self::getCollectionFromDb($sql);
        return $this->array_key_first($result);
    }

    // Ensure that current player has the right to empower piece_id with power
    function validate_power_action($power,$ship_id){
        $this->say('validating');
        self::checkAction('act_power_action');
        $player_id = $this->getActivePlayerId();

        // Check ship ownership
        $sql = 'SELECT piece_id,system_id FROM Pieces
            WHERE piece_id='.$ship_id.'
            AND owner_id='.$player_id;
        $result = self::getCollectionFromDb($sql);
        if(count($result) != 1){
            throw new BgaVisibleSystemException(
                self::_("You can only empower your own ships."));
        }

        // Check if free move has been used
        if(self::getGameStateValue('used_free') == 1)
            throw new BgaVisibleSystemException(
                self::_('Free action has already been used.'));

        // Check sacrifice power availability
        $sacrifice_color = self::getGameStateValue('sacrifice_color');
        if($sacrifice_color != 0){
            // This is a sacrifice turn, so the power must match
            // and there must be an action available
            if($sacrifice_color != $power){
                throw new BgaVisibleSystemException(
                    self::_('Technology must match sacrificed ship.'));
            }
            if(self::getGameStateValue('sacrifice_actions') <= 0){
                throw new BgaVisibleSystemException(
                    self::_('No sacrifice actions remaining.'));
            }
            // Sacrifice action check passed
            self::incGameStateValue('sacrifice_actions',-1);
            return;
        }

        // Check free power availability
        $ship = $result[$ship_id];
        $sql = 'SELECT piece_id FROM Pieces
            WHERE system_id='.$ship['system_id'].'
            AND (owner_id='.$player_id.' OR owner_id IS NULL)';
        $result = self::getCollectionFromDb($sql);
        if(count($result) == 0){
            throw new BgaVisibleSystemException(
                self::_('That technology is not available.'));
        }
        // Free action check passed
        self::setGameStateValue('used_free',1);
    }

    function get_piece_row($piece_id){
        return self::getCollectionFromDb(
            'SELECT * FROM Pieces WHERE piece_id='.$piece_id
        )[$piece_id];
    }

    function get_system_row($system_id){
        return self::getCollectionFromDb(
            'SELECT * FROM Systems WHERE system_id='.$system_id
        )[$system_id];
    }

    function get_stars($system_id){
        return self::getCollectionFromDb(
            'SELECT * FROM Pieces WHERE owner_id IS NULL AND system_id='.$system_id
        );
    }

    function get_player_row(){
        $player_id = $this->getCurrentPlayerId();
        $sql = 'SELECT * FROM player WHERE player_id='.$player_id;
        $result = self::getCollectionFromDb($sql);
        // We  have to do it this way since player_no is primary key
        return $result[$this->array_key_first($result)];
    }

    function is_empty($system_id){
        // Check for lack of ships
        $sql = 'SELECT piece_id FROM Pieces
            WHERE system_id='.$system_id.'
                AND owner_id IS NOT NULL';
        $ships = self::getCollectionFromDb($sql);
        if(count($ships)==0)
            return true;

        // Check for lack of stars
        $sql = 'SELECT piece_id FROM Pieces
            WHERE system_id='.$system_id.'
                AND owner_id IS NULL';
        $stars = self::getCollectionFromDb($sql);
        return count($stars)==0;
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////
    function creation($star1_id,$star2_id,$ship_id){
        // Player clicked a piece during creation
        // It should become a star or ship, depending on context
        self::checkAction('act_creation');
        $player_id = $this->getActivePlayerId();
        $player_name = $this->getActivePlayerName();

        [$system_id,$system_name] = $this->make_system(
            clienttranslate("Home of ${player_name}"),
            $player_id
        );

        $this->make_star($star1_id,$system_id);
        $this->make_star($star2_id,$system_id);
        $this->make_ship( $ship_id,$player_id,$system_id);

        self::notifyAllPlayers(
            'notif_create',
            clienttranslate('${player_name} establishes a homeworld.'),
            array(
                'homeplayer_id' => $player_id,
                'system_name'   => $player_name,
                'player_name'   => $player_name,
                'system_id'     => $system_id,
                'star1_id'      => $star1_id,
                'star2_id'      => $star2_id,
                'ship_id'       => $ship_id
            )
        );
        $this->gamestate->nextState('trans_after_creation');
    }
    function capture($piece_id,$capture_id){
        $this->validate_power_action(1,$piece_id);
        $attack_ship = $this->get_piece_row($piece_id);
        $target_ship = $this->get_piece_row($capture_id);

        // Check validity
        if($attack_ship['owner_id'] == $target_ship['owner_id']){
            throw new BgaVisibleSystemException(
                self::_('You may only capture enemy ships.'));
        }
        if($attack_ship['system_id'] != $target_ship['system_id']){
            throw new BgaVisibleSystemException(
                self::_('A ship may only capture ships in the same system.'));
        }
        if($attack_ship['pips'] < $target_ship['pips']){
            throw new BgaUserException(
                self::_('Attacking ship must not be smaller than target.'));
        }

        $player_id = $this->getActivePlayerId();
        $player_name = $this->getActivePlayerName();

        $sql = 'UPDATE Pieces
            SET owner_id='.$attack_ship['owner_id'].'
            WHERE piece_id='.$capture_id;
        self::DbQuery($sql);

        self::notifyAllPlayers(
            'notif_capture',
            clienttranslate('${player_name} captures a ship.'),
            array(
                'player_name' => $player_name,
                'target_id'   => $capture_id
            )
        );
        $this->gamestate->nextState('trans_after_power_action');
    }

    function move($ship_id,$system_id){
        $this->validate_power_action(2,$ship_id);

        $ship = $this->get_piece_row($ship_id);
        $old_system_id = $ship['system_id'];

        // Make sure systems are connected
        $old_stars = $this->get_stars($old_system_id);
        $new_stars = $this->get_stars($system_id);
        foreach($old_stars as $old_star_id => $old_star){
            foreach($new_stars as $new_star_id => $new_star){
                if($old_star['pips'] == $new_star['pips']){
                    throw new BgaUserException(
                        self::_('Systems are not connected.'));
                }
            }
        }
        
        $sql = 'UPDATE Pieces
            SET system_id='.$system_id.'
            WHERE piece_id='.$ship_id;
        self::DbQuery($sql);

        $system_name = $this->get_system_row($system_id)['system_name'];
        $player_name = $this->getActivePlayerName();
        self::notifyAllPlayers('notif_move',
            clienttranslate('${player_name} moves a ship to the ${system_name} system.'),
            array(
                'player_name' => $player_name,
                'system_id'   => $system_id,
                'ship_id'     => $ship_id,
                'system_name' => $system_name
            )
        );
        if($this->is_empty($old_system_id))
            $this->fade($old_system_id);

        $this->gamestate->nextState('trans_after_power_action');
    }

    function fade($system_id){
        // Notify client
        $system_name = $this->get_system_row($system_id)['system_name'];
        self::notifyAllPlayers('notif_fade',
            clienttranslate('The ${old_system_name} system fades.'),
            array(
                'system_id' => $system_id,
                'old_system_name' => $system_name
            )
        );
        // Put any remaining pieces in bank
        $sql = 'UPDATE Pieces
            SET system_id=NULL
            WHERE system_id='.$system_id;
        self::DbQuery($sql);

        // Remove system
        $sql = 'DELETE FROM Systems
            WHERE system_id='.$system_id;
        self::DbQuery($sql);
    }

    function discover($ship_id,$star_color_num,$star_pips){
        // Move will be validated by move function
        //$this->validate_power_action(2,$ship_id);

        // Make sure such a star is in the bank
        $star_id = $this->get_id_from_bank($star_color_num,$star_pips);
        if(is_null($star_id)){
            throw new BgaVisibleSystemException(
                self::_('No such piece in the bank.')
            );
        }
        [$system_id,$system_name] = $this->make_system();

        $player_name = $this->getActivePlayerName();
        self::notifyAllPlayers('notif_discover',
            clienttranslate('${player_name} discovers the ${system_name} system.'),
            array(
                'player_name' => $player_name,
                'system_id'   => $system_id,
                'star_id'     => $star_id,
                'system_name' => $system_name
            )
        );
        // Move star to the new system
        $sql = 'UPDATE Pieces
            SET system_id='.$system_id.'
            WHERE piece_id='.$star_id;
        self::DbQuery($sql);

        // State change will happen in move
        $this->move($ship_id,$system_id);
    }

    function build($ship_id){
        $this->validate_power_action(3,$ship_id);

        $old_ship = $this->get_piece_row($ship_id);
        $color = $old_ship['color'];
        // Get the smallest banked piece of that color
        $sql = 'SELECT piece_id,color,system_id,owner_id
            FROM Pieces
            WHERE color='.$color.'
                AND system_id IS NULL
            ORDER BY pips
            LIMIT 1';
        $result = self::getCollectionFromDb($sql);
        if(count($result)==0)
            throw new BgaUserException(
                self::_('That color is not available to build.'));

        $new_ship = $result[$this->array_key_first($result)];
        $system_id = $old_ship['system_id'];
        $this->make_ship($new_ship['piece_id'],$old_ship['owner_id'],$system_id);

        $player_name = $this->getActivePlayerName();
        $player_id   = $this->getActivePlayerId();
        self::notifyAllPlayers('notif_build',
            clienttranslate('${player_name} builds a ship.'),
            array(
                'player_name' => $player_name,
                'player_id'   => $player_id,
                'system_id'   => $system_id,
                'ship_id'     => $new_ship['piece_id']
            )
        );
        $this->gamestate->nextState('trans_after_power_action');
    }

    function trade($ship_id,$color_num){
        $this->validate_power_action(4,$ship_id);
        $old_ship  = $this->get_piece_row($ship_id);
        $system_id = $old_ship['system_id'];

        $new_ship_id = $this->get_id_from_bank($color_num,$old_ship['pips']);
        $this->make_ship($new_ship_id,$old_ship['owner_id'],$old_ship['system_id']);
        $this->put_in_bank($old_ship['piece_id']);

        $player_name = $this->getActivePlayerName();
        $player_id   = $this->getActivePlayerId();
        self::notifyAllPlayers('notif_trade',
            clienttranslate('${player_name} trades a ship.'),
            array(
                'player_name' => $player_name,
                'player_id'   => $player_id,
                'system_id'   => $system_id,
                'old_ship_id' => $ship_id,
                'new_ship_id' => $new_ship_id
            )
        );
        $this->gamestate->nextState('trans_after_power_action');
    }

    function sacrifice($ship_id){
        self::checkAction('act_sacrifice');

        $player_id = $this->getActivePlayerId();
        $player_name = $this->getActivePlayerName();

        // Make sure the ship's owner is corret
        $ship = $this->get_piece_row($ship_id);
        if($ship['owner_id'] != $player_id){
            throw new BgaVisibleSystemException(
                self::_('You may only sacrifice your own ships.')
            );
        }
        // We shouldn't need to separately check that they haven't already sacrificed
        // since act_sacrifice is only available in want_free state

        $this->put_in_bank($ship_id);

        self::setGameStateValue('sacrifice_color',$ship['color']);
        self::setGameStateValue('sacrifice_actions',$ship['pips']);

        self::notifyAllPlayers('notif_sacrifice',
            clienttranslate('${player_name} sacrifices a ship.'),
            array(
                'player_name' => $player_name,
                'ship_id' => $ship_id
            )
        );
        $system_id = $ship['system_id'];
        if($this->is_empty($system_id))
            $this->fade($system_id);
        $this->gamestate->nextState('trans_want_sacrifice_action');
    }

    function catastrophe($system_id,$color){
        self::checkAction('act_catastrophe');

        $player_name = $this->getActivePlayerName();

        if($this->is_empty($system_id))
            $this->fade($system_id);

        $sql = 'SELECT piece_id FROM Pieces
            WHERE system_id='.$system_id.'
                AND color='.$color;
        $piece_ids = array_keys(self::getCollectionFromDb($sql));

        // It takes four to make a catastrophe
        if(count($piece_ids)<4){
            throw new BgaVisibleSystemException(
                self::_('Catastrophes require four same-colored pieces in a system.')
            );
        }

        $this->put_in_bank($piece_ids);

        $system_name = $this->get_system_row($system_id)['system_name'];
        $color_name  = $this->color_names[$color-1];

        self::notifyAllPlayers('notif_catastrophe',
            clienttranslate('${player_name} triggers a ${color_name} catastrophe in ${system_name}.'),
            array(
                'player_name' => $player_name,
                'system_name' => $system_name,
                'color_name'  => $color_name,
                'system_id'   => $system_id,
                'color'       => $color
            )
        );
        $this->gamestate->nextState('trans_after_catastrophe');
    }

	function pass(){
        $player_name = $this->getActivePlayerName();
        self::notifyAllPlayers('notif_pass',
            clienttranslate('${player_name} ends their turn.'),
            array('player_name' => $player_name)
        );
        $this->gamestate->nextState('trans_end_turn');
    }
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*

    Example for game state "MyGameState":

    function argMyGameState() {
        // Get some values from the current game situation in database...

        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }
    */

    function args_want_sacrifice_action(){
        $action_color = self::getGameStateValue('sacrifice_color');
        $actions_remaining = self::getGameStateValue('sacrifice_actions');
        $action_name = $this->action_names[$action_color-1];
        //return array(
        //    'action_name' => 'temp action name',
        //    'actions_remaining' => 'temp action count'
        //);
        return array(
            'action_name' => $action_name,
            'color'      => $action_color,
            'actions_remaining' => $actions_remaining
        );
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////
    /*
    Functions to call automatically upon entry to a state
    */

    function st_after_creation(){
        $this->activeNextPlayer();
        // Figure out which state transition is needed
        $sql = 'SELECT system_id FROM Systems';
        $result = self::getCollectionFromDb($sql);
        $nhw = count($result);

        $sql = 'SELECT player_id FROM player';
        $result = self::getCollectionFromDb($sql);
        $nplayer = count($result);

        if($nhw==$nplayer)
            // All players are set up, go to normal turn
            $this->gamestate->nextState('trans_want_free');
        else
            // A player is not set up, go back to creation
            $this->gamestate->nextState('trans_want_creation');
    }

    function st_after_power_action(){
		$this->say('after power actioning');
        if(self::getGameStateValue('sacrifice_actions') > 0)
            $this->gamestate->nextState('trans_want_sacrifice_action');
		elseif($this->exist_overpopulations())
            $this->gamestate->nextState('trans_want_catastrophe');
        else
            $this->gamestate->nextState('trans_end_turn');
    }

	function st_after_catastrophe(){
        if(self::getGameStateValue('sacrifice_actions') > 0)
            $this->gamestate->nextState('trans_want_sacrifice_action');
        elseif(self::getGameStateValue('used_free') == 0)
            $this->gamestate->nextState('trans_want_free');
        elseif($this->exist_overpopulations())
            $this->gamestate->nextState('trans_want_catastrophe');
        else
            $this->gamestate->nextState('trans_end_turn');
    }

    function st_end_turn(){
        //  Check for win
        $sql = 'SELECT system_id,homeplayer_id FROM Systems
            WHERE homeplayer_id IS NOT NULL';
        $homeworlds = self::getCollectionFromDb($sql);

        $losers = [];
        foreach($homeworlds as $system_id => $system){
            $homeplayer_id = $system['homeplayer_id'];
            $sql = 'SELECT piece_id FROM Pieces
                WHERE system_id='.$system_id.'
                AND owner_id='.$homeplayer_id;
            $defenders = self::getCollectionFromDb($sql);
            if(count($defenders)==0){
                array_push($losers,$homeplayer_id);
            }
        }

        // Game is over
        if(count($losers)>0){
            if(count($losers)==1){
                $sql = 'UPDATE player
                    SET player_score=0
                    WHERE player_id='.$losers[0];
            }
            else{
                $sql = 'UPDATE player
                    SET player_score=50';
            }
            self::DbQuery($sql);
            $this->gamestate->nextState('trans_endGame');
            return;
        }
        self::setGameStateValue('used_free',0);
        self::setGameStateValue('sacrifice_color',0);
        self::setGameStateValue('sacrifice_actions',0);

        $this->activeNextPlayer();
        $this->gamestate->nextState('trans_want_free');
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:

        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).

        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message.
    */

    function zombieTurn( $state, $active_player ) {
        $statename = $state['name'];

        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                    break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );

            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }
///////////////////////////////////////////////////////////////////////////////////:

////////// DB upgrade
//////////

    /*
        upgradeTableDb:

        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.

    */

    function upgradeTableDb( $from_version ) {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345

        // Example:
//        if( $from_version <= 1404301345 ) {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB($sql);
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB($sql);
//        }
//        // Please add your future database scheme changes here
//
//
    }
}

