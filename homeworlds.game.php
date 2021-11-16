<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * Homeworlds implementation : © <Jonathan Baker> <babamots@gmail.com>
  *
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  *
  * homeworlds.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */

require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class homeworlds extends Table {
    function __construct( ) {
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();

        self::initGameStateLabels( array( 
            'used_free'         => 10,
            'sacrifice_color'   => 20,
            'sacrifice_actions' => 21,
            'draw_offerer'      => 30,
            'system_idx'        => 40,
            // Name list should be set by players in lobby
            'system_name_list_idx'  => 101,
            'system_name_start' => 41,
            'system_name_inc'   => 42
        ));
    }

    protected function getGameName( ) {
        // Used for translations and stuff. Please do not modify.
        return 'homeworlds';
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
        $sql .= implode(',',$values);
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

/* START DEBUG SETUP
        // Put a few pieces on the board to see if they're displayed properly
        //[$system_id,$null_name] = $this->make_system(2357472);
        [$system_id,$null_name] = $this->make_system($player_id);

        $this->make_star(1,$system_id);
        $this->make_star(31,$system_id);
        $this->make_ship(25,$player_id,$system_id);
        $this->make_ship(2,$player_id,$system_id);
        $this->make_ship(10,$player_id,$system_id);

        $this->make_ship(3,666,$system_id);
        $this->make_ship(7,666,$system_id);
//END DEBUG SETUP */

        // Change used_free to 1 when free move has been used
        // (This flag is needed in after_cat state to determine
        // whether to transition to want_free or want_catastrophe
        // if there has been no sacrifice)
        self::setGameStateInitialValue('used_free'  ,0);

        // Color zero indicates no sacrifice has occurred
        self::setGameStateInitialValue('sacrifice_color'  ,0);
        // Number of sacrifice actions available
        self::setGameStateInitialValue('sacrifice_actions',0);

        // ID of the player who has offered a draw
        // 0 if no draw has been offered
        // or -1 if the draw has been accepted (end game at once)
        self::setGameStateInitialValue('draw_offerer',0);

        // Number of systems that have been created
        self::setGameStateInitialValue('system_idx',0);

        // Prepare to access system name list
        $name_list_choice = self::getGameStateValue('system_name_list_idx');
        $system_names = $this->system_name_lists[$name_list_choice];
        $name_count = count($system_names);
        switch($name_list_choice){
            case 1:
                // nonsense
                // Start at the beginning with same order every time
                self::setGameStateInitialValue('system_name_start',0);
                self::setGameStateInitialValue('system_name_inc',1);
                // $this->system_name_start = 0;
                // $this->system_name_inc = 1;
                break;
            case 2:
                // real stars
                // Randomize the order of appearance (same as for fictional)
            case 3:
                // fictional
                // Randomize the first name and the name incrementation
                self::setGameStateInitialValue(
                    'system_name_start',
                    bga_rand(1,$name_count-1)
                );
                self::setGameStateInitialValue(
                    'system_name_inc',
                    bga_rand(1,$name_count-1)
                );
                // $this->system_name_start = self::bga_rand(1,$this->name_count-1);
                // $this->system_name_inc   = self::bga_rand(1,$this->name_count-1);
        }

        // Setup stats
        self::initStat('player','turns_number',0);
        self::initStat('player','ships_captured',0);
        self::initStat('player','systems_discovered',0);
        self::initStat('player','ships_built',0);
        self::initStat('player','ships_traded',0);
        self::initStat('player','ships_sacrificed',0);
        self::initStat('player','catastrophes_trigged',0);

        /************ End of the game initialization *****/

        self::activeNextPlayer();
    }

    function get_system_name_list(){
        $name_list_choice = self::getGameStateValue('system_name_list_idx');
        return $this->system_name_lists[$name_list_choice];
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
            player_name,
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
        $systems = [];
        $result['systems'] = &$systems;

        // Add stars and names to systems
        foreach($stars as $piece_id => $row){
            // The amperstand makes it a reference that can be changed
            $system_id = $row['system_id'];
            if(!array_key_exists($system_id,$systems)){
                $system = [];
                $systems[$system_id] = &$system;
                $system['name'] = $this->get_system_name($system_id);
                $system['stars'] = [];
                $system['ships'] = [];
            }
            $systems[$system_id]['stars'][$piece_id] = $row;
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
        // For Homeworlds, this is hard to estimate.
        // Let's say that progress is the proportion of pieces that are in play.
        $sql = 'SELECT piece_id FROM Pieces
            WHERE system_id IS NOT NULL';
        $result = self::getCollectionFromDb($sql);
        // Number of pieces in play
        $n_in_play = count($result);
        return intdiv(100*$n_in_play,36);
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

    function get_piece_string($piece_id){
        // THIS WILL BREAK IF PIECES ARE CONSTRUCTED IN A DIFFERENT ORDER
        // First piece has id 1
        $color = intdiv($piece_id-1,9)+1;
        $pips  = intdiv($piece_id-1-9*($color-1),3)+1;
        return $this->color_names_eng[$color][0].$pips;
    }

    function get_system_name($system_id){
        $num_players = $this->getPlayersNumber();
        $start = self::getGameStateValue('system_name_start');
        $inc = self::getGameStateValue('system_name_inc');
        $name_list = $this->get_system_name_list();
        $name_count = count($name_list);
        if($system_id<=$num_players){
            // This is a homeworld
            $sql = 'SELECT player_id,player_name FROM player
                WHERE homeworld_id='.$system_id;
            $result = self::getCollectionFromDb($sql);
            $homeplayer_id = $this->array_key_first($result);
            $player_name = $result[$homeplayer_id]['player_name'];
            return "Homeworld ${player_name}";
        }
        // This is NOT a homeworld
        $idx = $system_id-$num_players-1;
        $idx_loop = ( $idx*$inc + $start) % $name_count;

        $system_name = $name_list[$idx_loop];
        if($idx >= $name_count)
            // This system name has been used before, so suffix a number
            $system_name .= ' '.(1+intdiv($idx,$name_count));
        return $system_name;
    }

    function make_system($homeplayer_id=NULL){
        self::incGameStateValue('system_idx',1);
        $system_id = self::getGameStateValue('system_idx');
        if(!is_null($homeplayer_id)){
            // Set this as the player's homeworlds
            $sql = 'UPDATE player
                SET homeworld_id='.$system_id.'
                WHERE player_id='.$homeplayer_id;
            self::DbQuery($sql);
        }
        return $system_id;
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

    function get_homeplayer($system_id){
        $sql = 'SELECT player_id FROM player
            WHERE homeworld_id='.$system_id;
        $result = self::getCollectionFromDb($sql);
        return $this->array_key_first($result);
    }

    // Ensure that current player has the right to empower piece_id with power
    function validate_power_action($power,$ship_id){
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

    // For each piece in the list that isn't already saved,
    // put its current values in saved columns
    function save_state($piece_ids){
        $sql = 'UPDATE Pieces
            SET saved=TRUE,saved_system_id=system_id,saved_owner_id=owner_id
            WHERE NOT saved AND (piece_id=';
        if(!is_array($piece_ids)){
            // $piece_ids is a single ID
            $sql .= $piece_ids;
        }
        else{
            $sql .= implode(' OR piece_id=',$piece_ids);
        }
        $sql .= ')';
        self::DbQuery($sql);
    }

    // Put all saved values back into the regular columns
    // and set saved columns back to NULL.
    // Return the IDs of restored pieces
    function restore_state(){
        $sql = 'SELECT piece_id FROM Piecs
            WHERE saved';
        $result = self::getCollectionFromDb($sql);
        $sql = 'UPDATE Pieces
            SET system_id=saved_system_id,
                owner_id=saved_owner_id,
                saved=FALSE,
                saved_system_id=NULL,
                saved_owner_id=NULL
            WHERE saved';
        self::DbQuery($sql);
        return $result;
    }

    function get_piece_row($piece_id){
        return self::getCollectionFromDb(
            'SELECT * FROM Pieces WHERE piece_id='.$piece_id
        )[$piece_id];
    }

    function get_player_row($player_id){
        $sql = 'SELECT * FROM player WHERE player_id='.$player_id;
        $result = self::getCollectionFromDb($sql);
        return $result[$this->array_key_first($result)];
    }

    function get_stars($system_id){
        return self::getCollectionFromDb(
            'SELECT * FROM Pieces WHERE owner_id IS NULL AND system_id='.$system_id
        );
    }

    function get_containing_system($piece_id){
        $sql = 'SELECT piece_id,system_id FROM Pieces
            WHERE piece_id='.$piece_id;
        $result = self::getCollectionFromDb($sql);
        return $result[$piece_id]['system_id'];
    }

    function is_empty($system_id,$turn_over=false){
        // If the turn is not over and it's a home system, it's not empty
        if(!$turn_over && !is_null($this->get_homeplayer($system_id))){
            return false;
        }
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

        $system_id = $this->make_system($player_id);
        $system_name = $this->get_system_name($system_id);

        $this->make_star($star1_id,$system_id);
        $this->make_star($star2_id,$system_id);
        $this->make_ship( $ship_id,$player_id,$system_id);

        self::notifyAllPlayers(
            'notif_create',
            clienttranslate('${player_name} establishes a homeworld with a ${ship_str} ship at ${star1_str} and ${star2_str} binary stars.'),
            array(
                'homeplayer_id' => $player_id,
                'player_name'   => $player_name,
                'system_name'   => $system_name,
                'system_id'     => $system_id,
                'star1_id'      => $star1_id,
                'star2_id'      => $star2_id,
                'ship_id'       => $ship_id,
                'star1_str'      => $this->get_piece_string($star1_id),
                'star2_str'      => $this->get_piece_string($star2_id),
                'ship_str'       => $this->get_piece_string($ship_id)
            )
        );
        $this->gamestate->nextState('trans_after_creation');
    }

    function capture($piece_id,$capture_id){
        self::checkAction('act_power_action');
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

        $system_id = $this->get_containing_system($piece_id);
        $system_name = $this->get_system_name($system_id);

        self::notifyAllPlayers(
            'notif_capture',
            clienttranslate('${player_name} captures a ${target_str} ship in ${system_name}.'),
            array(
                'player_name' => $player_name,
                'target_id'   => $capture_id,
                'target_str'  => $this->get_piece_string($capture_id),
                'system_name' => $system_name
            )
        );
        $this->gamestate->nextState('trans_after_power_action');
    }

    function move($ship_id,$system_id){
        self::checkAction('act_power_action');
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

        $player_name = $this->getActivePlayerName();
        $system_name = $this->get_system_name($system_id);

        $old_system_name = $this->get_system_name($old_system_id);
        self::notifyAllPlayers('notif_move',
            clienttranslate('${player_name} moves a ${ship_str} ship from ${old_system_name} to ${system_name}.'),
            array(
                'player_name' => $player_name,
                'system_id'   => $system_id,
                'ship_id'     => $ship_id,
                'ship_str'    => $this->get_piece_string($ship_id),
                'system_name' => $system_name,
                'old_system_name' => $old_system_name
            )
        );
        if($this->is_empty($old_system_id))
            $this->fade($old_system_id);

        $this->gamestate->nextState('trans_after_power_action');
    }

    function fade($system_id){
        // Notify client
        $system_name = $this->get_system_name($system_id);
        self::notifyAllPlayers('notif_fade',
            clienttranslate('${old_system_name} is forgotten.'),
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

    }

    function discover($ship_id,$star_color_num,$star_pips){
        // Move will be validated by move function
        self::checkAction('act_power_action');
        //$this->validate_power_action(2,$ship_id);

        // Make sure such a star is in the bank
        $star_id = $this->get_id_from_bank($star_color_num,$star_pips);
        if(is_null($star_id)){
            throw new BgaVisibleSystemException(
                self::_('No such piece in the bank.')
            );
        }
        $system_id = $this->make_system();
        $system_name = $this->get_system_name($system_id);

        $player_name = $this->getActivePlayerName();
        self::notifyAllPlayers('notif_discover',
            clienttranslate('${player_name} discovers a ${star_str} system named ${system_name}.'),
            array(
                'player_name' => $player_name,
                'system_id'   => $system_id,
                'star_id'     => $star_id,
                'star_str'    => $this->get_piece_string($star_id),
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
        self::incStat(1,'systems_discovered',$this->getActivePlayerId());
    }

    function build($ship_id){
        self::checkAction('act_power_action');
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
        $system_name = $this->get_system_name($system_id);
        $this->make_ship($new_ship['piece_id'],$old_ship['owner_id'],$system_id);

        $player_name = $this->getActivePlayerName();
        $player_id   = $this->getActivePlayerId();
        self::notifyAllPlayers('notif_build',
            clienttranslate('${player_name} builds a ${ship_str} ship in ${system_name}.'),
            array(
                'player_name' => $player_name,
                'player_id'   => $player_id,
                'system_id'   => $system_id,
                'system_name' => $system_name,
                'ship_id'     => $new_ship['piece_id'],
                'ship_str'    => $this->get_piece_string($new_ship['piece_id'])
            )
        );
        $this->gamestate->nextState('trans_after_power_action');
        self::incStat(1,'ships_built',$this->getActivePlayerId());
    }

    function trade($ship_id,$color_num){
        self::checkAction('act_power_action');
        $this->validate_power_action(4,$ship_id);
        $old_ship  = $this->get_piece_row($ship_id);
        $system_id = $old_ship['system_id'];
        $system_name = $this->get_system_name($system_id);

        $new_ship_id = $this->get_id_from_bank($color_num,$old_ship['pips']);
        $this->make_ship($new_ship_id,$old_ship['owner_id'],$old_ship['system_id']);
        $this->put_in_bank($old_ship['piece_id']);

        $player_name = $this->getActivePlayerName();
        $player_id   = $this->getActivePlayerId();
        self::notifyAllPlayers('notif_trade',
            clienttranslate('${player_name} trades a ${old_ship_str} ship for a ${new_ship_str} ship in ${system_name}.'),
            array(
                'player_name'  => $player_name,
                'player_id'    => $player_id,
                'system_id'    => $system_id,
                'system_name'  => $system_name,
                'old_ship_id'  => $ship_id,
                'old_ship_str' => $this->get_piece_string($ship_id),
                'new_ship_id'  => $new_ship_id,
                'new_ship_str' => $this->get_piece_string($new_ship_id)
            )
        );
        $this->gamestate->nextState('trans_after_power_action');
        self::incStat(1,'ships_traded',$player_id);
    }

    function sacrifice($ship_id){
        self::checkAction('act_sacrifice');

        $player_id = $this->getActivePlayerId();
        $player_name = $this->getActivePlayerName();

        $system_id = $this->get_containing_system($ship_id);
        $system_name = $this->get_system_name($system_id);

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
            clienttranslate('${player_name} sacrifices a ${ship_str} ship in ${system_name}.'),
            array(
                'player_name' => $player_name,
                'ship_id' => $ship_id,
                'ship_str' => $this->get_piece_string($ship_id),
                'system_name' => $system_name
            )
        );
        $system_id = $ship['system_id'];
        if($this->is_empty($system_id))
            $this->fade($system_id);
        $this->gamestate->nextState('trans_want_sacrifice_action');
        self::incStat(1,'ships_sacrificed',$player_id);
    }

    function catastrophe($system_id,$color){
        self::checkAction('act_catastrophe');

        $player_name = $this->getActivePlayerName();

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

        $system_name = $this->get_system_name($system_id);
        $color_name  = $this->color_names_local[$color];

        self::notifyAllPlayers('notif_catastrophe',
            clienttranslate('${player_name} triggers a ${color_name} catastrophe in ${system_name}.'),
            array(
                'i18n' => array('color_name'),
                'player_name' => $player_name,
                'system_name' => $system_name,
                'color_name'  => $color_name,
                'system_id'   => $system_id,
                'color'       => $color
            )
        );

        if($this->is_empty($system_id))
            $this->fade($system_id);

        $this->gamestate->nextState('trans_after_catastrophe');
        self::incStat(1,'catastrophes_trigged',$this->getActivePlayerId());
    }

	function pass(){
        self::checkAction('act_pass');
        $player_name = $this->getActivePlayerName();
        self::notifyAllPlayers('notif_pass',
            clienttranslate('${player_name} ends their turn.'),
            array('player_name' => $player_name)
        );
        $this->gamestate->nextState('trans_end_turn');
    }

	function offer_draw(){
        self::checkAction('act_offer_draw');
        $player_name = $this->getActivePlayerName();
        $player_id = $this->getActivePlayerId();

        $previous_offerer = self::getGameStateValue('draw_offerer');
        if($previous_offerer == $player_id){
            throw new BgaVisibleSystemException(
                self::_('You have already offered a draw.')
            );
        }
        else if($previous_offerer==0){
            // This is the first offer
            self::notifyAllPlayers('notif_offer_draw',
                clienttranslate('${player_name} offers a draw.'),
                array('player_name' => $player_name)
            );
            self::setGameStateValue('draw_offerer',$player_id);
        }
        else{
            // Opponent offered a draw and it has just been accepted
            self::notifyAllPlayers('notif_offer_draw',
                clienttranslate('${player_name} accepts the draw.'),
                array('player_name' => $player_name)
            );
            self::setGameStateValue('draw_offerer',-1);
            $this->gamestate->nextState('trans_end_turn');
        }
    }

	function cancel_offer_draw(){
        self::checkAction('act_cancel_offer_draw');
        $player_name = $this->getActivePlayerName();
        $player_id = $this->getActivePlayerId();

        $previous_offerer = self::getGameStateValue('draw_offerer');
        if($previous_offerer != $player_id){
            throw new BgaVisibleSystemException(
                self::_('You do not have an active draw offer.')
            );
        }
        else{
            self::notifyAllPlayers('notif_cancel_offer_draw',
                clienttranslate('${player_name} cancels draw offer.'),
                array('player_name' => $player_name)
            );
            self::setGameStateValue('draw_offerer',0);
        }
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

    function args_want_free(){
        return array(
            'draw_offerer' => self::getGameStateValue('draw_offerer')
        );
    }
    function args_want_sacrifice_action(){
        $action_color = self::getGameStateValue('sacrifice_color');
        $actions_remaining = self::getGameStateValue('sacrifice_actions');
        $action_name = $this->action_names[$action_color];
        //return array(
        //    'action_name' => 'temp action name',
        //    'actions_remaining' => 'temp action count'
        //);
        return array(
            'action_name' => $action_name,
            'color'      => $action_color,
            'actions_remaining' => $actions_remaining,
            'draw_offerer' => self::getGameStateValue('draw_offerer')
        );
    }
    function args_want_catastrophe(){
        return array(
            'draw_offerer' => self::getGameStateValue('draw_offerer')
        );
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////
    /*
    Functions to call automatically upon entry to a state
    */
    function st_after_creation(){
        // Skip any zombie turns
        do{
            $this->activeNextPlayer();
            $player_id = $this->getActivePlayerId();
        }while($this->isPlayerZombie($player_id));

        $sql = 'SELECT player_id FROM player
            WHERE player_id='.$player_id.'
            AND homeworld_id IS NOT NULL';
        $result = self::getCollectionFromDb($sql);
        if(count($result) > 0){
            // This player has a home, so everyone has had a chance to create
            // Go to normal turn
            $this->gamestate->nextState('trans_want_free');
            return;
        }
        $this->gamestate->nextState('trans_want_creation');
    }

    function st_after_power_action(){
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

    // This is called after every turn except for creations
    function st_end_turn(){
        $sql = 'SELECT player_id,player_name,homeworld_id FROM player';
        $players = self::getCollectionFromDb($sql);

        if(self::getGameStateValue('draw_offerer') < 0){
            // Players agreed to end the game in a draw
            $sql = 'UPDATE player
                SET player_score=50';
            self::DbQuery($sql);
            $this->gamestate->nextState('trans_endGame');
            return;
        }

        // Empty homeworlds fade at this point
        // Check for loss conditions
        $losers = [];
        foreach($players as $player_id => $player){
            $homeworld_id = $player['homeworld_id'];
            // Check for totally empty/destroyed homeworld
            if($this->is_empty($homeworld_id,true))
                $this->fade($homeworld_id);
            // Check for homeworld that is not occupied by its owner
            $sql = 'SELECT piece_id FROM Pieces
                WHERE system_id='.$homeworld_id.'
                AND owner_id='.$player_id;
            $defenders = self::getCollectionFromDb($sql);
            if(count($defenders)==0){
                array_push($losers,$player_id);
                $player_name = $player['player_name'];
                self::notifyAllPlayers(
                    'notif_elimination',
                    clienttranslate('${player_name} has no ships in their homeworld and is eliminated.'),
                    array(
                        'player_name'   => $player_name
                    )
                );
            }
        }

        // Game is over
        if(count($losers)>0){
            // All scores start at 100, only update scores of losers
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

        $player_id = $this->getActivePlayerId();
        $this->giveExtraTime($player_id);

        $this->activeNextPlayer();
        $this->gamestate->nextState('trans_want_free');
        self::incStat(1,'turns_number',$player_id);
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
        // If a player is missing, just end the zombie turn.
        $this->gamestate->nextState('zombiePass');
    }

	function isPlayerZombie($player_id) {
	   $players = self::loadPlayersBasicInfos();
	   if (! isset($players[$player_id]))
		   throw new feException("Player $player_id is not playing here");
	   
	   return ($players[$player_id]['player_zombie'] == 1);
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

