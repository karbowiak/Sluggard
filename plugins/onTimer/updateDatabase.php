<?php

use Sluggard\SluggardApp;

/**
 * Class updateDatabase
 */
class updateDatabase {
    /**
     * @var SluggardApp
     */
    private $app;
    /**
     * @var \Sluggard\Lib\config
     */
    private $config;
    /**
     * @var \Discord\Discord
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
     * updateDatabase constructor.
     * @param $discord
     * @param SluggardApp $app
     */
    public function __construct($discord, SluggardApp $app) {
        $this->app = $app;
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
     * When a message arrives that contains a trigger, this is started
     *
     * @param $msgData
     */
    public function onMessage($msgData) {

    }

    /**
     * When the bot starts, this is started
     */
    public function onStart() {

    }

    /**
     * When the bot does a tick (every second), this is started
     */
    public function onTick() {

    }

    /**
     * When the bot's tick hits a specified time, this is started
     *
     * Runtime is defined in $this->information(), timerFrequency
     */
    public function onTimer() {
        $this->log->info("Checking for an update to the CCP Database");
        $this->updateCCPDB();
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
            "name" => "updateDatabase",
            "trigger" => array(""),
            "information" => "",
            "timerFrequency" => 43200
        );
    }

    /**
     *
     */
    private function updateCCPDB() {
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
                $this->log->warn("Error updating the CCPDatabase:" . $e->getMessage());
                die();
            }
        }
    }
}