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

$this->system_name_lists = array(
    1 => [
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
    ],
    2 => [
        'Billy'
    ],
    3 => [
        // Focal planet of the "Dragonriders of Pern" novels
        'Pern',
        // (Dune) Focal planet of Frank Herbert's "Dune" series
        'Arrakis',
        // Homeworld of Luke Skywalker in "Star Wars"
        'Tatooine',
        // Residence planet of Yoda in "Star Wars"
        'Dagobah',
        // Homeworld of the Masters in the "Tripods" novels
        'Trion',
        // Homeworld of the Klingons in "Star Trek"
        'Qo\'noS',
        // Homeworld of the Vulcans in "Star Trek"
        'Vulcan',
        // Focal planet of "The Lord of the Rings" novels
        'Arda',
        // Focal planet of the "Discworld" novels
        'Discworld',
        // Homeworld of Kal-El (Superman) in D.C. Comics
        'Krypton',
        //Homeworld of the Transformers in "Transformers"
        'Cybertron',
        // Homeworld of the Time Lords in "Doctor Who"
        'Gallifrey',
        // Location of the planet builders in "The Hitchhiker's Guide to the Galaxy" novels
        'Magrathea',
        // Human capital world in "Battlestar Galactica"
        'Caprica',
        // Homeworld of the Arachnids (Bugs) in "Starship Troopers"
        'Klendathu',
        // Homeworld of the Pequeninos in the "Ender Quintet" novels
        'Lusitania',
        // (Acheron) Location of main events in "Alien" films
        'LV-426',
        // Capital world of the Galactic Empire in the "Foundation" novels
        'Trantor',
        // The focal megastructure of the "Ringworld" novels
        'Ringworld',
        // Focal planet of "The Vision of Escaflowne." Also a planet in "No Man's Sky." Also a term for Earth or its personification as a Greek god.
        'Gaea',
        // Homeworld of the Alteans in "Voltron"
        'Altea',
        // Focal planet of "Solaris"
        'Solaris',
        // Repeated setting in "Firefly"
        'Persephone',
        // Captial world of the Alliance in "Firefly"
        'Ariel',
        // Terran homeworld in "StarCraft"
        'Tarsonis',
        // Zerg base planet in "StarCraft"
        'Char',
        // Protoss homeworld in "StarCraft"
        'Aiur',
        // Focal planet in "Warcraft"
        'Azeroth',
        // Homeworld of the Orcs in "Warcraft"
        'Draenor',
        // Focal planet in "Avatar"
        'Pandora',
        // Frequently seen planet in many works
        'Earth',
    ],
);

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

