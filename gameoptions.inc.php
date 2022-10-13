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
 * gameoptions.inc.php
 *
 * Homeworlds game options description
 * 
 * In this file, you can define your game options (= game variants).
 *   
 * Note: If your game has no variant, you don't have to modify this file.
 *
 * Note²: All options defined in this file should have a corresponding "game state labels"
 *        with the same ID (see "initGameStateLabels" in homeworlds.game.php)
 *
 * !! It is not a good idea to modify this file when a game is running !!
 *
 */

$game_options = array(
    101 => array(
        'name' => totranslate('Star names'),
        'values' => array(
            1 => array(
                'name' => totranslate('Nonsense star names')
            ),
            2 => array(
                'name' => totranslate('Real-world star names')
            ),
            3 => array(
                'name' => totranslate('World names from popular fiction')
            )
        )
    )
);

$game_preferences = array(
    102 => array(
        'name' => totranslate('Colorblind mode'),
        // You wouldn't think so, but the cssPref class only gets applied on page load
        'needReload' => true,
        'values' => array(
            0 => array(
				'name' => totranslate('Off'),
				'cssPref' => 'colorblind_off'
            ),
            1 => array(
                'name' => totranslate('On'),
                'cssPref' => 'colorblind_on'
            )
        ),
        'default' => 0
    ),
    105 => array(
        'name' => totranslate('Background'),
        // You wouldn't think so, but the cssPref class only gets applied on page load
        'needReload' => true,
        'values' => array(
            1 => array(
                'name' => totranslate('Stars'),
                'cssPref' => 'hw_bg_stars'
            ),
            0 => array(
				'name' => totranslate('BGA default'),
				'cssPref' => 'hw_bg_default'
            ),
        ),
        'default' => 1
    ),
    103 => array(
        'name' => totranslate('Power selection method'),
        // You wouldn't think so, but the cssPref class only gets applied on page load
        'needReload' => true,
        'values' => array(
            1 => array(
                'name' => totranslate('Buttons or pieces'),
                'cssPref' => 'power_buttons_on'
            ),
            0 => array(
				'name' => totranslate('Pieces only'),
				'cssPref' => 'power_buttons_off'
            ),
        ),
        'default' => 1
    ),
    104 => array(
        'name' => totranslate('Power reference labels'),
        // You wouldn't think so, but the cssPref class only gets applied on page load
        'needReload' => true,
        'values' => array(
            0 => array(
				'name' => totranslate('Off'),
				'cssPref' => 'legend_off'
            ),
            1 => array(
                'name' => totranslate('On'),
                'cssPref' => 'legend_on'
            ),
        ),
        'default' => 1
    )
);


