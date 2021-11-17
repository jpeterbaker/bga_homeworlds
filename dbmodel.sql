
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- Homeworlds implementation : © <Jonathan Baker> <babamots@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

-- On Jonathan's computer, player schema described in ~/bga/player_schema.png

ALTER TABLE `player` ADD `homeworld_id` SMALLINT UNSIGNED DEFAULT NULL;

-- Each of the 36 pieces will be listed individually
CREATE TABLE IF NOT EXISTS `Pieces` (
    -- getCollectionFromDb uses keys as array indices,
    -- so it will be convenient to have a single key field
    `piece_id` int NOT NULL PRIMARY KEY AUTO_INCREMENT,
    -- Piece color (1-4)
    `color` tinyint UNSIGNED NOT NULL,
    -- Piece size (1-3)
    `pips` tinyint UNSIGNED NOT NULL,
    -- System that this piece occupies (null for bank)
    -- Corresponds to Systems.system_id
    `system_id` smallint UNSIGNED DEFAULT NULL,
    -- Play-order number of owning player (null for star or bank)
    -- Corresponds to player.player_id
    `owner_id` int UNSIGNED DEFAULT NULL,
    -- True if values have been remembered in the saved_* columns
    -- `saved` BOOLEAN NOT NULL DEFAULT FALSE,
    -- Saved values of system_id and owner_id for reverting to start of turn
    `saved_system_id` smallint UNSIGNED DEFAULT NULL,
    `saved_owner_id` int UNSIGNED DEFAULT NULL
) ENGINE=InnoDB;

-- CREATE TABLE IF NOT EXISTS `Systems` (
--     `system_id` int NOT NULL PRIMARY KEY AUTO_INCREMENT,
--     `system_name` VARCHAR(40) DEFAULT NULL,
--     -- Corresponds to player.player_id or NULL for a colony
--     `homeplayer_id` int UNSIGNED DEFAULT NULL
-- ) ENGINE=InnoDB;

