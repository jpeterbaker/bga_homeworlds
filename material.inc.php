<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Homeworlds implementation : © Jonathan Baker <babamots@gmail.com>
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









    // WORLDS FROM FICTION
    // To avoid duplicates from appearing due to the randomization routine,
    // the number of entries in the list should be prime.
    // Starting the list on line 101 makes it easy to check the number of entries
    // To avoid worlds from the same universe from appearing in succession,
    // don't put them next to each other or at equal intervals
    // (a random order is probably best)

    3 => [
        'Pern', // Focal planet of the "Dragonriders of Pern" books
        'Arrakis', // (Dune) Focal planet of Frank Herbert's "Dune" series
        'Dagobah', // Residence planet of Yoda in "Star Wars"
        'Trion', // Homeworld of the Masters in the "Tripods" books
        'Qo\'noS', // Homeworld of the Klingons in "Star Trek"
        'Azeroth', // Focal planet in "Warcraft"
        'Arda', // Focal planet of "The Lord of the Rings" books
        'Discworld', // Focal planet of the "Discworld" books
        'Krypton', // Homeworld of Kal-El (Superman) in D.C. Comics
        'Cybertron', //Homeworld of the Transformers in "Transformers"
        'Cardassia', // Homeworld of Dukat in "Star Trek: Deep Space Nine"
        'Gallifrey', // Homeworld of the Time Lords in "Doctor Who"
        'Magrathea', // Location of the planet builders in "The Hitchhiker's Guide to the Galaxy" books
        'Vulcan', // Homeworld of the Vulcans in "Star Trek"
        'Caprica', // Human capital world in "Battlestar Galactica"
        'Klendathu', // Homeworld of the Arachnids (Bugs) in "Starship Troopers"
        'Lusitania', // Homeworld of the Pequeninos in the "Ender Quintet" books
        'LV-426', // (Acheron) Location of main events in "Alien" films
        'Trantor', // Capital world of the Galactic Empire in the "Foundation" books
        'Ringworld', // The focal megastructure of the "Ringworld" books
        'Altea', // Homeworld of the Alteans in "Voltron"
        'Solaris', // Focal planet of "Solaris"
        'Aiur', // Protoss homeworld in "StarCraft"
        'Persephone', // Repeated setting in "Firefly." Also a planet in "Elite Dangerous"
        'Ariel', // Captial world of the Alliance in "Firefly"
        'Tarsonis', // Terran homeworld in "StarCraft"
        'Draenor', // Homeworld of the Orcs in "Warcraft"
        'Pandora', // Focal planet in "Avatar." Also a central planet in "Borderlands"
        'Earth', // Frequent setting in many fictional works
        'Tatooine', // Homeworld of Luke Skywalker in "Star Wars"
        'Reach', // Human military hub in "Halo"
        'Asgard', // Homeworld of Thor in Marvel Comics
        'Ego', // The living planet in Marvel Comics
        'Camazotz', // Major setting in "A Wrinkle in Time"
        'Teufel', // Homeworld of Hans Rebka in the "Heritage" books
        'Skaro', // Homeworld of the Daleks in "Doctor Who"
        'Char', // Zerg base planet in "StarCraft"
        'Tarn-Vedra', // Homeworld of the Vedrans in "Andromeda"
        'Minbar', // Homeworld of the Minbari in "Babylon 5"
        'Chulak', // Homeworld of Teal'c in "Stargate"
        'Hera', // Location of the pivotal battle of Serenity in "Firefly." Also the name of worlds in "Dragonball Z" and "Borderlands."
        'Promethea', // Prominent world in "Borderlands"
        'Druidia', // Potential victim planet in "Spaceballs"
        'Antarea', // Homeworld of the Antareans in "Cocoon"
        'Mongo', // Focal planet of "Flash Gordon"
        'Bespin', // Prominent world in "The Empire Strikes Back"
        'Priplanus', // Focal planet of "Lost in Space"
        'Risa', // Pleasure planet in "Star Trek" universe
        'Polus', // Major setting of "Among Us"
        'Oa', // Headquarters of the Green Lantern Corps
        'Zok', // A planet visited by Calvin/Spaceman Spiff. Also a TV Trope
        'Yuggoth', // (Pluto, possibly) Homeworld of the Mi-go in the Lovecraft Mythos
        'Sagittaron', // Homeworld of Tom Zarek in "Battlestar Galactica"
        'Mesklin', // Recurring planet in works of Hal Clement
        'Hyperion', // Planet from Dan Simmons' Hyperion Cantos
        'Earthsea', // Ursula K. LeGuin's Tales from Earthsea
        'Krynn', // (A gameworld of D&D) Setting of the "Dragonlance Chronicles"
        'Xanth', // Setting of the "Xanth" books
        'Urth', // (New name for Earth) Setting of "Solar Cycle" books
        'Riverworld', // Setting of the "Riverworld" books
        'Hoth', // Prominent world in "The Empire Strikes Back"
        'Bajor', // Homeworld of Kira in "Star Trek: Deep Space Nine"
        'Betazed', // Homeworld of Betazoids in "Star Trek: The Next Generation"
        'Kobol', // Legendary human homeworld in "Battlestar Galactica"
        'Wonderland', // Setting of "Alice's Adventures in Wonderland"
        'Oz', // Setting of the "Oz" books (somewhere on Earth)
        'Narnia', // Setting of the "Chronicles of Narnia" books
        'Vogsphere', // Homeworld of Vogons in "The Hitchhiker's Guide to the Galaxy" books
        'Snaiad', // Focal planet of a scientific fiction project by C. M. Kosemen
        'Gaea', // Focal planet of "The Vision of Escaflowne." Also a planet in "No Man's Sky." Also a term for Earth or its personification as a Greek god.
        'Balaraphon', // Planet in Dimension X in "Teenage Mutant Ninja Turtles"
    ],
);

// These terms will never be used as verbs
// They will be used as labels for buttons, labels for piece stacks,
// and in the phease "3 ${action_name} action(s) remaining"
$this->action_names = array(
    1 => clienttranslate('Capture'),
    2 => clienttranslate('Move'),
    3 => clienttranslate('Build'),
    4 => clienttranslate('Trade')
);
$this->color_names_eng = array(
    1 => 'red',
    2 => 'yellow',
    3 => 'green',
    4 => 'blue'
);
$this->color_names_local = array(
    1 => clienttranslate('Red'),
    2 => clienttranslate('Yellow'),
    3 => clienttranslate('Green'),
    4 => clienttranslate('Blue')
);

