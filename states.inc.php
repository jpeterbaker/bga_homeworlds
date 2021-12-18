<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Homeworlds implementation : © <Jonathan Baker> <babamots@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 * states.inc.php
 *
 * Homeworlds game states description

action:
    Name of a PHP function to call on entry to this state.
    These traditionally start with st
    In the case of the starting state, there's no stGameSetup function 
    but it exists somewhere in code hidden from developers.

args:
    Name of a function that will produce parameters to pass to
    the JS function onEnteringState when this state is enteted.
    Returned array can include values that get substituted into the descriptions

name:
    Name of the state.

type:
    One of
        manager (for start/end state),
        game (a processing state with no active players),
        activeplayer (a state waiting for one player to move),
        multipleactiveplayer (a state waiting for multiple players to move)

description:
    Text to appear in status bar.
    You can update this description with some trickery
    See "Changing state prompt" on
    https://en.doc.boardgamearena.com/BGA_Studio_Cookbook

descriptionmyturn:
    Text to appear in status bar for the active player(s)

transitions:
    An array mapping transition names to state ids

possibleactions:
    An array of the names of possible actions that the player could take
    These are NOT the same as transitions
    TODO
    When I figure out exactly how they're used, I should expand this note

updateGameProgression:
    true or false (false is default)
    Set to true if you want the getGameProgression() function to be called
 */

$machinestates = array(

    // The initial state.
    // Please do not modify.
    1 => array(
        'name' => 'gameSetup',
        'description' => '',
        'type' => 'manager',
        'action' => 'stGameSetup',
        'transitions' => array('' => 10) // PRODUCTION
        //'transitions' => array('' => 11) // TESTING
    ),

    //////////////////////
    // Get player input //
    //////////////////////
    10 => array(
        'name' => 'want_creation',
        'description' => clienttranslate('${actplayer} must create a homeworld.'),
        'descriptionmyturn' => clienttranslate('${you} must choose a homestar from the bank.'),
        'type' => 'activeplayer',
        'possibleactions' => array( 'act_creation' ),
        'transitions' => array(
            'trans_after_creation' => 20,
            'zombiePass' => 20
        )
    ),
    // Get free action, sacrifice, catastrophe, or pass
    11 => array(
        'name' => 'want_free',
        'description' => clienttranslate('${actplayer} may activate or sacrifice a ship.'),
        'descriptionmyturn' => clienttranslate('${you} may choose a ship to activate or sacrifice.'),
        'type' => 'activeplayer',
        'updateGameProgression' => true,
        'possibleactions' => array(
            'act_power_action',
            'act_sacrifice',
            'act_catastrophe',
            'act_pass',
            'act_restart_turn',
            'act_offer_draw',
            'act_cancel_offer_draw'
        ),
        'args' => 'args_want_free',
        'transitions' => array(
            'trans_after_power_action' => 21,
            'trans_want_sacrifice_action' => 12,
            'trans_after_catastrophe' => 23,
            'trans_end_turn' => 30,
            'trans_restart' => 11,
            'zombiePass' => 30
        )
    ),
    // Get sac action, catastrophe, or pass
    12 => array(
        'name' => 'want_sacrifice_action',
        'description' => clienttranslate('${actplayer} may ${action_name} a ship (${actions_remaining} action(s) remaining).'),
        'descriptionmyturn' => clienttranslate('${you} may choose a ship to activate (${actions_remaining} ${action_name} action(s) remaining).'),
        'type' => 'activeplayer',
        'possibleactions' => array(
            'act_power_action',
            'act_catastrophe',
            'act_pass',
            'act_restart_turn',
            'act_offer_draw',
            'act_cancel_offer_draw'
        ),
        'args' => 'args_want_sacrifice_action',
        'transitions' => array(
            'trans_after_power_action' => 21,
            'trans_after_catastrophe' => 23,
            'trans_end_turn' => 30,
            'trans_restart' => 11,
            'zombiePass' => 30
        )
    ),
    13 => array(
        'name' => 'want_catastrophe',
        'description' => clienttranslate('${actplayer} may trigger a catastrophe.'),
        'descriptionmyturn' => clienttranslate('${you} may trigger a catastrophe.'),
        'type' => 'activeplayer',
        'possibleactions' => array(
            'act_catastrophe',
            'act_pass',
            'act_restart_turn',
            'act_offer_draw',
            'act_cancel_offer_draw'
        ),
        'args' => 'args_want_catastrophe',
        'transitions' => array(
            'trans_after_catastrophe' => 23,
            'trans_end_turn' => 30,
            'trans_restart' => 11,
            'zombiePass' => 30
        )
    ),
    14 => array(
        'name' => 'want_restart_turn',
        'description' => clienttranslate('${actplayer} may restart their turn.'),
        'descriptionmyturn' => clienttranslate('${you} may restart your turn.'),
        'type' => 'activeplayer',
        'possibleactions' => array(
            'act_pass',
            'act_restart_turn',
            'act_offer_draw',
            'act_cancel_offer_draw'
        ),
        'args' => 'args_want_restart_turn',
        'transitions' => array(
            'trans_end_turn' => 30,
            'trans_restart' => 11,
            'zombiePass' => 30
        )
    ),
    ///////////////////
    // Process input //
    ///////////////////
    20 => array(
        'name' => 'after_creation',
        'type' => 'game',
        'action' => 'st_after_creation',
        'transitions' => array(
            'trans_want_creation' => 10 ,
            'trans_want_free' => 11,
            'trans_skip_zombie' => 20
        )
    ),
    21 => array(
        'name' => 'after_power_action',
        'type' => 'game',
        'action' => 'st_after_power_action',
        'transitions' => array(
            'trans_end_turn' => 30,
            'trans_want_sacrifice_action' => 12,
            'trans_want_catastrophe' => 13,
            'trans_want_restart_turn' =>14
        )
    ),
    23 => array(
        'name' => 'after_catastrophe',
        'type' => 'game',
        'action' => 'st_after_catastrophe',
        'transitions' => array(
            'trans_want_free' => 11,
            'trans_want_sacrifice_action' => 12,
            'trans_want_catastrophe' => 13,
            'trans_want_restart_turn' =>14,
            'trans_end_turn' => 30,
        )
    ),
    30 => array(
        'name' => 'end_turn',
        'type' => 'game',
        'action' => 'st_end_turn',
        'transitions' => array(
            'trans_want_free' => 11 ,
            'trans_endGame' => 99
        )
    ),
    // Final state
    // Please do not modify and do not overload action/args methods
    99 => array(
        'name' => 'gameEnd',
        'description' => clienttranslate('End of game'),
        'type' => 'manager',
        'action' => 'stGameEnd',
        'args' => 'argGameEnd'
    )
);

