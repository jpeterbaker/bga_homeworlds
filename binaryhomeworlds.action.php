<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * binaryHomeworlds implementation : © <Jonathan Baker> <babamots@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 *
 * binaryhomeworlds.action.php
 *
 * binaryHomeworlds main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/binaryhomeworlds/binaryhomeworlds/myAction.html", ...)
 *
 */


class action_binaryhomeworlds extends APP_GameAction {
    // Constructor: please do not modify
    public function __default() {
        if( self::isArg( 'notifwindow') ) {
            $this->view = "common_notifwindow";
            $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
        }
        else {
            $this->view = "binaryhomeworlds_binaryhomeworlds";
            self::trace( "Complete reinitialization of board game" );
        }
    }

    // Define your action entry points there
    public function act_creation(){
        self::setAjaxMode();
        $star1_id = self::getArg('star1_id','AT_posint',true);
        $star2_id = self::getArg('star2_id','AT_posint',true);
        $ship_id  = self::getArg('ship_id' ,'AT_posint',true);
        $this->game->creation($star1_id,$star2_id,$ship_id);
        self::ajaxResponse();
    }

    public function act_power_action(){
        self::setAjaxMode();
        $piece_id = self::getArg('piece_id','AT_posint',true);
        $power = self::getArg('power','AT_posint',true);
        switch($power){
            case 1:
                $capture_id = self::getArg('capture_id','AT_posint',true);
                $this->game->capture($piece_id,$capture_id);
                break;
            case 2:
                $is_discovery = self::getArg('is_discovery','AT_bool',true);
                if($is_discovery){
                    $star_color_num = self::getArg('star_color_num','AT_posint',true);
                    $star_pips      = self::getArg('star_pips'     ,'AT_posint',true);
                    $this->game->discover($piece_id,$star_color_num,$star_pips);
                }
                else{
                    $system_id = self::getArg('system_id','AT_posint',true);
                    $this->game->move($piece_id,$system_id);
                }
                break;
            case 3:
                $this->game->build($piece_id);
                break;
            case 4:
                $color_num = self::getArg('color_num','AT_posint',true);
                $this->game->trade($piece_id,$color_num);
                break;
        }
        self::ajaxResponse();
    }

    public function act_sacrifice(){
        self::setAjaxMode();
        $ship_id = self::getArg('ship_id','AT_posint',true);
        $this->game->sacrifice($ship_id);
        self::ajaxResponse();
    }

    public function act_catastrophe(){
        self::setAjaxMode();
        $system_id = self::getArg('system_id','AT_posint',true);
        $color = self::getArg('color','AT_posint',true);
        //$this->game->catastrophe($system_id,$color);
        self::ajaxResponse();
    }
}


