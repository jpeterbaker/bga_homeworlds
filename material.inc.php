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
        'Altair',
        'Betelgeuse',
        'Castor',
        'Deneb',
        'Electra',
        'Fomalhaut',
        'Gemma',
        'Hadar',
        'Izar',
        'Jabbah',
        'Kastra',
        'Lesath',
        'Menkar',
        'Nashira',
        'Okul',
        'Polaris',
        'Rigel',
        'Sirius',
        'Taygeta',
        'Ursa Major',
        'Vega',
        'Zosma',
    ],

















    3 => [
        // Focal planet of the "Dragonriders of Pern" novels
        'Pern',
        // (Dune) Focal planet of Frank Herbert's "Dune" series
        'Arrakis',
        // Residence planet of Yoda in "Star Wars"
        'Dagobah',
        // Homeworld of the Masters in the "Tripods" novels
        'Trion',
        // Homeworld of the Klingons in "Star Trek"
        'Qo\'noS',
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
        // Homeworld of the Vulcans in "Star Trek"
        'Vulcan',
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
        // Protoss homeworld in "StarCraft"
        'Aiur',
        // Repeated setting in "Firefly." Also a planet in "Elite Dangerous"
        'Persephone',
        // Captial world of the Alliance in "Firefly"
        'Ariel',
        // Terran homeworld in "StarCraft"
        'Tarsonis',
        // Focal planet in "Warcraft"
        'Azeroth',
        // Homeworld of the Orcs in "Warcraft"
        'Draenor',
        // Focal planet in "Avatar." Also a central planet in "Borderlands"
        'Pandora',
        // Frequent setting in many fictional works
        'Earth',
        // Homeworld of Luke Skywalker in "Star Wars"
        'Tatooine',
        // Human military hub in "Halo"
        'Reach',
        // Homeworld of Thor in Marvel Comics
        'Asgard',
        // The living planet in Marvel Comics
        'Ego',
        // Major setting in "A Wrinkle in Time"
        'Camazotz',
        // Homeworld of Hans Rebka in the "Heritage" novels
        'Teufel',
        // Homeworld of the Daleks in "Doctor Who"
        'Skaro',
        // Zerg base planet in "StarCraft"
        'Char',
        // Homeworld of the Vedrans in "Andromeda"
        'Tarn-Vedra',
        // Homeworld of the Minbari in "Babylon 5"
        'Minbar',
        // Homeworld of Teal'c in "Stargate"
        'Chulak',
        // Location of the pivotal battle of Serenity in "Firefly." Also a world in Dragonball Z
        'Hera',
        // Obscure planet in Dimension X in "Teenage Mutant Ninja Turtles"
        'Balaraphon',
        // Prominent world in "Borderlands"
        'Promethea',
        // Potential victim planet in "Spaceballs"
        'Druidia',
        // Homeworld of the Antareans in "Cocoon"
        'Antarea',
        // Focal planet of "Flash Gordon"
        'Mongo',
        // Focal planet of "Lost in Space"
        'Priplanus',
        // Major setting of "Among Us"
        'Polus',
        // Focal planet of a scientific fiction project by C. M. Kosemen
        'Snaiad',
        // Headquarters of the Green Lantern Corps
        'Oa',
        // A planet visited by Calvin/Spaceman Spiff. Also a TV Trope
        'Zok',
        // (Pluto, possibly) Homeworld of the Mi-go in the Lovecraft Mythos
        'Yuggoth',
        // Recurring planet in works of Hal Clement
        'Mesklin',
        // Planet from Dan Simmons' Hyperion Cantos
        'Hyperion',
        // Ursula K. LeGuin's Tales from Earthsea
        'Earthsea',
        // Dragonlance Chronicles (one of the two first gameworlds of D&D)
        'Krynn',
        // Piers Anthony's Xanth novels
        'Xanth',
        // Gene Wolfe New Sun books
        'Urth',
        // Philip Jose Farmer's Riverworld series
        'Riverworld',
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

