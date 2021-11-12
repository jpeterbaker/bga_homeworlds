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
 * material.inc.php
 *
 * Homeworlds game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
Example:

$this->card_types = array(
    1 => array( "card_name" => ...,
                ...
              )
);

*/

// TODO make a second list in case a player has one of these names

$this->system_names = array(
    'Alti',
    'Bleb',
    'Cemori',
    'Dioj',
    'Ewarop',
    'Fost',
    'Gabrit',
    'Hutranox',
    'Ipru',
    'Jupple',
    'Ke',
    'Limspun',
    'Meequee',
    'Nabstrinamo',
    'Omp',
    'Pupooro',
    'Quir',
    'Rih',
    'Sulminori',
    'Tamb',
    'Usaelo',
    'Vawk',
    'Wofryd',
    'Xebd',
    'Yokrip',
    'Zeeha'
);
$this->system_names[-1] = 'SECRET';

$this->name_count = count($this->system_names);

// These terms will only be used in phrases of the form
// 3 ${action_name} actions remaining
$this->action_names = array(
    1 => clienttranslate('capture'),
    2 => clienttranslate('move'),
    3 => clienttranslate('build'),
    4 => clienttranslate('trade')
);
$this->color_names_eng = array(
    1 => 'red',
    2 => 'yellow',
    3 => 'green',
    4 => 'blue'
);
$this->color_names_local = array(
    1 => clienttranslate('red'),
    2 => clienttranslate('yellow'),
    3 => clienttranslate('green'),
    4 => clienttranslate('blue')
);

