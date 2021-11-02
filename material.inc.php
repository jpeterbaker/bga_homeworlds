<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * binaryHomeworlds implementation : © <Jonathan Baker> <babamots@gmail.com>
 * 
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * material.inc.php
 *
 * binaryHomeworlds game material description
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
    /*
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
    */
);
$this->name_count = count($this->system_names);

$this->action_names = array(
    1=>'capture',
    2=>'move',
    3=>'build',
    4=>'trade'
);

