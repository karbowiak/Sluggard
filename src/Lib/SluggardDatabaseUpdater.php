<?php

namespace Sluggard\Lib;


use Sluggard\SluggardApp;

/**
 * Class SluggardDatabaseUpdater
 * @package Sluggard\Lib
 */
class SluggardDatabaseUpdater
{
    /**
     * @var SluggardApp
     */
    private $app;
    /**
     * @var \Sluggard\Models\SluggardData
     */
    private $sluggardDB;
    /**
     * @var log
     */
    private $log;
    /**
     * @var config
     */
    private $config;

    /**
     * SluggardDatabaseUpdater constructor.
     * @param SluggardApp $app
     */
    function __construct(SluggardApp &$app) {
        $this->app = $app;
        $this->log = $app->log;
        $this->config = $app->config;
        $this->sluggardDB = $app->sluggarddata;
    }

    /**
     *
     */
    public function createSluggardDB() {
        $tables = array("users", "usersSeen", "storage", "authentications");

        $tableCreateCode = array(
            "users" => "
            BEGIN;
            CREATE TABLE IF NOT EXISTS `users` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `serverID` BIGINT(20) NOT NULL,
                `userID` BIGINT(20) NOT NULL,
                `discordID` BIGINT(20) NOT NULL,
                `characterID` INT(16) NOT NULL,
                `corporationID` VARCHAR(255) NOT NULL,
                `allianceID` VARCHAR(255) NOT NULL,
                `authString` VARCHAR(255) NOT NULL,
                `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
            CREATE UNIQUE INDEX userID ON users (userID);
            CREATE INDEX serverID ON users (serverID);
            CREATE INDEX corporationID ON users (corporationID);
            CREATE INDEX allianceID ON users (allianceID);
            COMMIT;",
            "usersSeen" => "
            BEGIN;
            CREATE TABLE IF NOT EXISTS `usersSeen` (
                `id` INTEGER PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `isAdmin` TINYINT(1) NOT NULL DEFAULT '0',
                `lastSeen` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `lastSpoke` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                `lastStatus` VARCHAR(50) NULL DEFAULT NULL,
                `lastWritten` TEXT NULL
            );
            CREATE INDEX name ON usersSeen (name);
            COMMIT;",
            "storage" => "
            BEGIN;
            CREATE TABLE IF NOT EXISTS `storage` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `key` VARCHAR(255) NOT NULL,
                `value` VARCHAR(255) NOT NULL
            );
            CREATE UNIQUE INDEX key ON storage (key);
            COMMIT;",
            "authentications" => "
            BEGIN;
            CREATE TABLE IF NOT EXISTS `authentications` (
                `discordID` INTEGER PRIMARY KEY,
                `characterID` BIGINT(40) NOT NULL,
                `corporationID` BIGINT(40) NOT NULL,
                `allianceID` BIGINT(40) NOT NULL,
                `guildID` BIGINT(128) DEFAULT 0 NOT NULL
            );
            CREATE UNIQUE INDEX discordID ON authentications (discordID);
            COMMIT;
            ",
            "fittings" => "
            BEGIN;
            CREATE TABLE `fittings` (
                `guildID` INTEGER PRIMARY KEY,
                `fittingName` VARCHAR(255) NOT NULL,
                `fittingURL` VARCHAR(255) NOT NULL
            );
            CREATE UNIQUE INDEX fittingName ON fittings (guildID, fittingName);
            COMMIT;"
        );

        $dbName = $this->config->get("botName", "bot");
        // Does the file exist?
        if(!file_exists(BASEDIR . "/config/database/{$dbName}.sqlite"))
            touch(BASEDIR . "/config/database/{$dbName}.sqlite");

        // Check if the tables exist, if not, create them
        foreach($tables as $table) {
            $exists = $this->sluggardDB->queryField("SELECT name FROM sqlite_master WHERE type = 'table' AND name = :name", "name", array(":name" => $table));
            if(!$exists) {
                $this->log->warn("Creating {$table} in {$dbName}.sqlite, since it does not exist");
                $this->sluggardDB->execute(trim($tableCreateCode[$table]));
            }
        }
    }
}