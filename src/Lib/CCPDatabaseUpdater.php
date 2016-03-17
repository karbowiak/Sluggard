<?php

namespace Sluggard\Lib;

use Sluggard\SluggardApp;

/**
 * Class CCPDatabaseUpdater
 * @package Sluggard\Lib
 */
class CCPDatabaseUpdater
{
    /**
     * @var SluggardApp
     */
    private $app;
    /**
     * @var cURL
     */
    private $curl;
    /**
     * @var Storage
     */
    private $storage;
    /**
     * @var log
     */
    private $log;
    /**
     * @var \Sluggard\Models\CCPData
     */
    private $ccpDB;

    /**
     * CCPDatabaseUpdater constructor.
     * @param SluggardApp $app
     */
    function __construct(SluggardApp &$app) {
        $this->app = $app;
        $this->curl = $app->curl;
        $this->storage = $app->storage;
        $this->log = $app->log;
        $this->ccpDB = $app->ccpdata;
    }

    /**
     *
     */
    public function createCCPDB() {
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