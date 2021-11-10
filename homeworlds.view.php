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
 * homeworlds.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in homeworlds_homeworlds.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */

require_once( APP_BASE_PATH.'view/common/game.view.php' );

class view_homeworlds_homeworlds extends game_view {
    function getGameName() {
        return 'homeworlds';
    }
    function build_page( $viewArgs ) {
        // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count( $players );

        /*********** Place your code below:  ************/

        $this->page->begin_block('homeworlds_homeworlds','stack');
        for($color=1;$color<=4;$color++){
            for($pips=1;$pips<=3;$pips++){
                $left = ($pips-1)*33.3;
                $top  = ($color-1)*25;
                $this->page->insert_block(
                    'stack',
                    array(
                        'COLOR' => $color,
                        'PIPS'  => $pips,
                        'LEFT'  => $left,
                        'TOP'   => $top
                    )
                );
            }
        }

        /*********** Do not change anything below this line  ************/
    }
}


