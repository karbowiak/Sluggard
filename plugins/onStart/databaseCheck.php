<?php
use Sluggard\SluggardApp;

/**
 * Class databaseCheck
 */
class databaseCheck {
    /**
     * @var \Sluggard\Lib\config
     */
    private $config;
    /**
     * @var
     */
    private $discord;
    /**
     * @var \Sluggard\Lib\log
     */
    private $log;
    /**
     * @var \Sluggard\Models\SluggardData
     */
    private $sluggardDB;
    /**
     * @var \Sluggard\Models\CCPData
     */
    private $ccpDB;
    /**
     * @var \Sluggard\Lib\cURL
     */
    private $curl;
    /**
     * @var \Sluggard\Lib\Storage
     */
    private $storage;
    /**
     * @var \Sluggard\Lib\triggerCommand
     */
    private $trigger;

    /**
     * databaseCheck constructor.
     * @param $discord
     * @param SluggardApp $app
     */
    public function __construct($discord, SluggardApp $app) {
        $this->config = $app->config;
        $this->discord = $discord;
        $this->log = $app->log;
        $this->sluggardDB = $app->sluggarddata;
        $this->ccpDB = $app->ccpdata;
        $this->curl = $app->curl;
        $this->storage = $app->storage;
        $this->trigger = $app->triggercommand;
    }

    /**
     * @param $msgData
     */
    public function onMessage($msgData) {
    }

    /**
     *
     */
    public function onStart() {
        // Check all the directories exist (cache, config, config/database)
        $directories = array(BASEDIR . "/cache/", BASEDIR . "/config/", BASEDIR . "/config/database");

        foreach($directories as $directory) {
            if(!file_exists($directory)) {
                $this->log->info("Creating {$directory}");
                mkdir($directory);
                chmod($directory, 0755);
            }
        }
        
        // Create the databases if they don't exist
        $this->createSluggardDB();
        $this->createCCPDB();
    }

    /**
     *
     */
    public function onTick() {
    }

    /**
     *
     */
    public function onTimer() {
    }

    /**
     * @return array
     *
     * name: is the name of the script
     * trigger: is an array of triggers that can trigger this plugin
     * information: is a short description of the plugin
     * timerFrequency: if this were an onTimer script, it would execute every x seconds, as defined by timerFrequency
     */
    public function information() {
        return array(
            "name" => "",
            "trigger" => array(""),
            "information" => "",
            "timerFrequency" => 0
        );
    }

    /**
     *
     */
    private function createSluggardDB() {
        $tables = array("users", "usersSeen", "storage");

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
            COMMIT;"
        );

        // Does the file exist?
        if(!file_exists(BASEDIR . "/config/database/sluggard.sqlite"))
            touch(BASEDIR . "/config/database/sluggard.sqlite");

        // Check if the tables exist, if not, create them
        foreach($tables as $table) {
            $exists = $this->sluggardDB->queryField("SELECT name FROM sqlite_master WHERE type = 'table' AND name = :name", "name", array(":name" => $table));
            if(!$exists) {
                $this->log->warn("Creating {$table} in sluggard.sqlite, since it does not exist");
                $this->sluggardDB->execute(trim($tableCreateCode[$table]));
            }
        }
    }

    /**
     *
     */
    private function createCCPDB() {
        $ccpDataURL = "https://www.fuzzwork.co.uk/dump/sqlite-latest.sqlite.bz2";
        $ccpDataMD5URL = "https://www.fuzzwork.co.uk/dump/sqlite-latest.sqlite.bz2.md5";
        $dbLocation = BASEDIR . "/config/database/";

        $md5 = explode(" ", $this->curl->getData($ccpDataMD5URL))[0];
        $lastSeenMd5 = $this->storage->get("ccpDataMd5");

        // If the last seen md5, isn't equal the current seen md5, we'll update!
        if($lastSeenMd5 !== $md5) {
            try {
                $this->log->notice("Updating CCP SQLite Database");
                $this->log->notice("Downloading bz2 file, and writing it to {$dbLocation}ccpData.sqlite.bz2");

                $downloadedData = $this->curl->getLargeData($ccpDataURL, "{$dbLocation}ccpData.sqlite.bz2");

                if($downloadedData == false) {
                    $this->log->warn("Error: File not downloaded successfully!");
                    die();
                }

                $this->log->notice("Opening bz2 file");
                $sqliteData = bzopen("{$dbLocation}ccpData.sqlite.bz2", "r");

                $this->log->notice("Reading from bz2 file");
                $data = "";
                while(!feof($sqliteData))
                    $data .= bzread($sqliteData, 4096);

                $this->log->notice("Writing bz2 file contents into .sqlite file");
                file_put_contents("{$dbLocation}ccpData.sqlite", $data);

                $this->log->notice("Deleting bz2 file");
                unlink("{$dbLocation}ccpData.sqlite.bz2");

                $this->log->notice("Creating mapCelestials view");
                $this->ccpDB->execute("CREATE VIEW mapAllCelestials AS SELECT itemID, itemName, typeName, mapDenormalize.typeID, solarSystemName, mapDenormalize.solarSystemID, mapDenormalize.constellationID, mapDenormalize.regionID, mapRegions.regionName, orbitID, mapDenormalize.x, mapDenormalize.y, mapDenormalize.z FROM mapDenormalize JOIN invTypes ON (mapDenormalize.typeID = invTypes.typeID) JOIN mapSolarSystems ON (mapSolarSystems.solarSystemID = mapDenormalize.solarSystemID) JOIN mapRegions ON (mapDenormalize.regionID = mapRegions.regionID) JOIN mapConstellations ON (mapDenormalize.constellationID = mapConstellations.constellationID)");

                $this->log->notice("CCP Database updated!");

                $this->storage->set("ccpDataMd5", $md5);
            } catch(\Exception $e) {
                $this->log->warn("Error updating the CCPDatabase. Bot can't run");
                die();
            }
        }
    }
}