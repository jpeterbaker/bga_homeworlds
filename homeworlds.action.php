<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Homeworlds implementation : © Jonathan Baker <babamots@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 *
 * homeworlds.action.php
 *
 * Homeworlds main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/homeworlds/homeworlds/myAction.html", ...)
 *
 */


class action_homeworlds extends APP_GameAction {
    // Constructor: please do not modify
    public function __default() {
        if( self::isArg( 'notifwindow') ) {
            $this->view = "common_notifwindow";
            $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
        }
        else {
            $this->view = "homeworlds_homeworlds";
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
        $power = self::getArg('power','AT_posint',true);
        $piece_id = self::getArg('piece_id','AT_posint',false);
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
                $color_num = self::getArg('color_num','AT_posint',true);
                $system_id = self::getArg('system_id','AT_posint',true);
                $this->game->build($color_num,$system_id);
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
        $this->game->catastrophe($system_id,$color);
        self::ajaxResponse();
    }

    public function act_pass(){
        self::setAjaxMode();
        $repeat_verified = self::getArg('repeat_verified','AT_bool',true);
        $this->game->pass($repeat_verified);
        self::ajaxResponse();
    }

    public function act_restart_turn(){
        self::setAjaxMode();
        $this->game->restart();
        self::ajaxResponse();
    }

    public function act_offer_draw(){
        self::setAjaxMode();
        $this->game->offer_draw();
        self::ajaxResponse();
    }

    public function act_cancel_offer_draw(){
        self::setAjaxMode();
        $this->game->cancel_offer_draw();
        self::ajaxResponse();
    }
}


