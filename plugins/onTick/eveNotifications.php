<?php

use Sluggard\SluggardApp;

/**
 * Class eveNotifications
 */
class eveNotifications {
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
     * @private
     */
    private $nextCheck;
    /**
     * @private
     */
    private $toDiscordChannel;
    /**
     * @private
     */
    private $newestNotificationID;
    /**
     * @private
     */
    private $maxID;
    /**
     * @private
     */
    private $keyCount;
    /**
     * @private
     */
    private $keys;
    /**
     * @var
     */
    var $charApi;
    /**
     * @var
     */
    var $corpApi;
    /**
     * @var
     */
    var $alliApi;

    /**
     * eveNotifications constructor.
     * @param $discord
     * @param SluggardApp $app
     */
    public function __construct(&$discord, SluggardApp &$app) {
        $this->app = $app;
        $this->config = $app->config;
        $this->discord = $discord;
        $this->log = $app->log;
        $this->sluggardDB = $app->sluggarddata;
        $this->ccpDB = $app->ccpdata;
        $this->curl = $app->curl;
        $this->storage = $app->storage;
        $this->trigger = $app->triggercommand;

        // Rena APIs
        $this->charApi = "http://rena.karbowiak.dk/api/character/information/";
        $this->corpApi = "http://rena.karbowiak.dk/api/corporation/information/";
        $this->alliApi = "http://rena.karbowiak.dk/api/alliance/information/";

        $this->toDiscordChannel = $this->config->get("channelID", "evemails");
        $this->newestNotificationID = $this->storage->get("newestNotificationID");
        $this->maxID = 0;
        $this->keyCount = count($this->config->get("apiKeys", "eve"));
        $this->keys = $this->config->get("apiKeys", "eve");
        $this->nextCheck = 0;

        // Schedule all the apiKeys for the future
        $keyCounter = 0;
        foreach($this->keys as $keyOwner => $apiData) {
            $keyID = $apiData["keyID"];
            if($apiData["corpKey"] == true)
                continue;
            $characterID = $apiData["characterID"];

            if($keyCounter == 0) // Schedule it for right now
                $this->storage->set("corpMailCheck{$keyID}{$keyOwner}{$characterID}", time() - 5);
            else {
                $rescheduleTime = time() + ((1805 / $this->keyCount) * $keyCounter);
                $this->storage->set("corpMailCheck{$keyID}{$keyOwner}{$characterID}", $rescheduleTime);
            }
            $keyCounter++;
        }
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
        $check = true;
        foreach ($this->keys as $keyOwner => $api) {
            try {
                if ($check == false)
                    continue;

                $keyID = $api["keyID"];
                $vCode = $api["vCode"];
                if($api["corpKey"] == true)
                    continue;
                
                $characterID = $api["characterID"];
                $lastChecked = $this->storage->get("notificationCheck{$keyID}{$keyOwner}{$characterID}");

                if ($lastChecked <= time()) {
                    $this->log->info("Checking API Key {$keyID} belonging to {$keyOwner} for new notifications");
                    $this->getNotifications($keyID, $vCode, $characterID);
                    $this->storage->set("notificationCheck{$keyID}{$keyOwner}{$characterID}", time() + 1805); // Reschedule it's check for 30minutes from now (Plus 5s, ~CCP~)
                    $check = false;
                }
            } catch (\Exception $e) {
                $this->log->err("Error with eve notification checker: " . $e->getMessage());
            }
        }
    }

    /**
     * When the bot's tick hits a specified time, this is started
     *
     * Runtime is defined in $this->information(), timerFrequency
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
            "name" => "eveNotifications",
            "trigger" => array(""),
            "information" => "",
            "timerFrequency" => 0
        );
    }


    /**
     * @param $keyID
     * @param $vCode
     * @param $characterID
     */
    private function getNotifications($keyID, $vCode, $characterID)
    {
        try { // Seriously CCP.. *sigh*
            // Ignore notifications from these douchebags..
            $ignoreNames = array("CCP");
            $url = "https://api.eveonline.com/char/Notifications.xml.aspx?keyID={$keyID}&vCode={$vCode}&characterID={$characterID}";
            $data = json_decode(json_encode(simplexml_load_string($this->curl->getData($url), "SimpleXMLElement", LIBXML_NOCDATA)), true);
            $data = $data["result"]["rowset"]["row"];

            // If there is no data, just quit..
            if (empty($data))
                return;

            $fixedData = array();

            // Sometimes there is only ONE notification, so.. yeah..
            if (count($data) > 1) {
                foreach ($data as $getFuckedCCP)
                    $fixedData[] = $getFuckedCCP["@attributes"];
            } else
                $fixedData[] = $data["@attributes"];

            foreach ($fixedData as $notification) {
                $notificationID = $notification["notificationID"];
                $typeID = $notification["typeID"];
                //$senderID = $notification["senderID"];
                $senderName = $notification["senderName"];
                $sentDate = $notification["sentDate"];
                //$read = $notification["read"];

                // If the senderName is in the list of ignores names, then continue and ignore it..
                if (in_array($senderName, $ignoreNames))
                    continue;

                if ($notificationID > $this->newestNotificationID) {
                    $notificationString = explode("\n", $this->getNotificationText($keyID, $vCode, $characterID, $notificationID));

                    $msg = null;

                    // Seriously, get fucked CCP
                    switch ($typeID) {
                        case 5: // War Declared
                            $aggressorAllianceID = trim(explode(": ", $notificationString[2])[1]);
                            $aggressorAllianceName = $this->apiData("alli", $aggressorAllianceID)["allianceName"];
                            $delayHours = trim(explode(": ", $notificationString[3])[1]);
                            $msg = "War declared by {$aggressorAllianceName}. Fighting begins in roughly {$delayHours} hours.";
                            break;

                        case 8: // Alliance war invalidated by CONCORD
                            $aggressorAllianceID = trim(explode(": ", $notificationString[2])[1]);
                            $aggressorAllianceName = $this->apiData("alli", $aggressorAllianceID)["allianceName"];
                            $msg = "War declared by {$aggressorAllianceName} has been invalidated. Fighting ends in roughly 24 hours.";
                            break;

                        case 75: // POS / POS Module under attack
                            $aggressorAllianceID = trim(explode(": ", $notificationString[0])[1]);
                            $aggressorAllianceName = $this->apiData("alli", $aggressorAllianceID)["allianceName"];
                            $aggressorCorpID = trim(explode(": ", $notificationString[1])[1]);
                            $aggressorCorpName = $this->apiData("corp", $aggressorCorpID)["corporationName"];
                            $aggressorID = trim(explode(": ", $notificationString[2])[1]);
                            $aggressorCharacterName = $this->apiData("char", $aggressorID)["characterName"];
                            $armorValue = trim(explode(": ", $notificationString[3])[1]);
                            $hullValue = trim(explode(": ", $notificationString[4])[1]);
                            $moonID = trim(explode(": ", $notificationString[5])[1]);
                            $moonName = $this-$this->ccpDB->queryField("SELECT itemName FROM mapAllCelestials WHERE itemID = :id", "itemName", array(":id" => $moonID));
                            $shieldValue = trim(explode(": ", $notificationString[6])[1]);
                            $solarSystemID = trim(explode(": ", $notificationString[7])[1]);
                            $systemName = $this->ccpDB->queryField("SELECT solarSystemName FROM mapSolarSystems WHERE solarSystemID = :id", "solarSystemName", array(":id" => $solarSystemID));

                            $msg = "POS under attack in **{$systemName} - {$moonName}** by {$aggressorCharacterName} ({$aggressorCorpName} / {$aggressorAllianceName}). Status: Hull: {$hullValue}, Armor: {$armorValue}, Shield: {$shieldValue}";
                            break;

                        case 76: // Tower resource alert
                            $moonID = trim(explode(": ", $notificationString[2])[1]);
                            $moonName = $this->ccpDB->queryField("SELECT itemName FROM mapAllCelestials WHERE itemID = :id", "itemName", array(":id" => $moonID));
                            $solarSystemID = trim(explode(": ", $notificationString[3])[1]);
                            $systemName = $this->ccpDB->queryField("SELECT solarSystemName FROM mapSolarSystems WHERE solarSystemID = :id", "solarSystemName", array(":id" => $solarSystemID));
                            $blocksRemaining = trim(explode(": ", $notificationString[6])[1]);
                            $typeID = trim(explode(": ", $notificationString[7])[1]);
                            $typeName = $this->ccpDB->queryField("SELECT typeName FROM invTypes WHERE typeID = :id", "typeName", array(":id" => $typeID));

                            $msg = "POS in {$systemName} - {$moonName} needs fuel. Only {$blocksRemaining} {$typeName}'s remaining.";
                            break;
                        
                        case 88: // IHUB is being attacked
                            $aggressorAllianceID = trim(explode(": ", $notificationString[0])[1]);
                            $aggressorAllianceName = $this->apiData("alli", $aggressorAllianceID)["allianceName"];
                            $aggressorCorpID = trim(explode(": ", $notificationString[0])[1]);
                            $aggressorCorpName = $this->apiData("corp", $aggressorCorpID)["corporationName"];
                            $aggressorID = trim(explode(": ", $notificationString[1])[1]);
                            $aggressorCharacterName = $this->apiData("char", $aggressorID)["characterName"];
                            $armorValue = trim(explode(": ", $notificationString[3])[1]);
                            $hullValue = trim(explode(": ", $notificationString[4])[1]);
                            $shieldValue = trim(explode(": ", $notificationString[5])[1]);
                            $solarSystemID = trim(explode(": ", $notificationString[6])[1]);
                            $systemName = $this->ccpDB->queryField("SELECT solarSystemName FROM mapSolarSystems WHERE solarSystemID = :id", "solarSystemName", array(":id" => $solarSystemID));

                            $msg = "IHUB under attack in **{$systemName}** by {$aggressorCharacterName} ({$aggressorCorpName} / {$aggressorAllianceName}). Status: Hull: {$hullValue}, Armor: {$armorValue}, Shield: {$shieldValue}";
                            break;

                        case 93: // Customs office is being attacked
                            $aggressorAllianceID = trim(explode(": ", $notificationString[0])[1]);
                            $aggressorAllianceName = $this->apiData("alli", $aggressorAllianceID)["allianceName"];
                            $aggressorCorpID = trim(explode(": ", $notificationString[0])[1]);
                            $aggressorCorpName = $this->apiData("corp", $aggressorCorpID)["corporationName"];
                            $aggressorID = trim(explode(": ", $notificationString[2])[1]);
                            $aggressorCharacterName = $this->apiData("char", $aggressorID)["characterName"];
                            $planetID = trim(explode(": ", $notificationString[3])[1]);
                            $planetName = $this->ccpDB->queryField("SELECT itemName FROM mapAllCelestials WHERE itemID = :id", "itemName", array(":id" => $planetID));
                            $shieldValue = trim(explode(": ", $notificationString[5])[1]);
                            $solarSystemID = trim(explode(": ", $notificationString[6])[1]);
                            $systemName = $this->ccpDB->queryField("SELECT solarSystemName FROM mapSolarSystems WHERE solarSystemID = :id", "solarSystemName", array(":id" => $solarSystemID));
                            $typeID = trim(explode(": ", $notificationString[7])[1]);
                            $typeName = $this->ccpDB->queryField("SELECT typeName FROM invTypes WHERE typeID = :id", "typeName", array(":id" => $typeID));

                            $msg = "Customs Office under attack in **{$systemName}** ($planetName) by {$aggressorCharacterName} ({$aggressorCorpName} / {$aggressorAllianceName}). Shield Status: {$shieldValue}";
                            break;

                        case 147: // Entosis has stated
                            $systemID = trim(explode(": ", $notificationString[0])[1]);
                            $systemName = $this->ccpDB->queryField("SELECT solarSystemName FROM mapSolarSystems WHERE solarSystemID = :id", "solarSystemName", array(":id" => $systemID));
                            $typeID = trim(explode(": ", $notificationString[1])[1]);
                            $typeName = $this->ccpDB->queryField("SELECT typeName FROM invTypes WHERE typeID = :id", "typeName", array(":id" => $typeID));

                            $msg = "Entosis has started in **{$systemName}** on **{$typeName}** (Date: **{$sentDate}**)";
                            break;

                        case 148: // Entosis enabled a module ??????
                            $systemID = trim(explode(": ", $notificationString[0])[1]);
                            $systemName = $this->ccpDB->queryField("SELECT solarSystemName FROM mapSolarSystems WHERE solarSystemID = :id", "solarSystemName", array(":id" => $systemID));
                            $typeID = trim(explode(": ", $notificationString[1])[1]);
                            $typeName = $this->ccpDB->queryField("SELECT typeName FROM invTypes WHERE typeID = :id", "typeName", array(":id" => $typeID));

                            $msg = "Entosis has enabled a module in **{$systemName}** on **{$typeName}** (Date: **{$sentDate}**)";
                            break;

                        case 149: // Entosis disabled a module
                            $systemID = trim(explode(": ", $notificationString[0])[1]);
                            $systemName = $this->ccpDB->queryField("SELECT solarSystemName FROM mapSolarSystems WHERE solarSystemID = :id", "solarSystemName", array(":id" => $systemID));
                            $typeID = trim(explode(": ", $notificationString[1])[1]);
                            $typeName = $this->ccpDB->queryField("SELECT typeName FROM invTypes WHERE typeID = :id", "typeName", array(":id" => $typeID));

                            $msg = "Entosis has disabled a module in **{$systemName}** on **{$typeName}** (Date: **{$sentDate}**)";
                            break;
                        case 160: // Entosis successful
                            $msg = "Hostile entosis successful. Structure has entered reinforced mode. (Unfortunately this api endpoint doesn't provide any more details)";
                            break;
                        case 161: // Command Nodes Decloaking
                            $systemID = trim(explode(": ", $notificationString[2])[1]);
                            $systemName = $this->ccpDB->queryField("SELECT solarSystemName FROM mapSolarSystems WHERE solarSystemID = :id", "solarSystemName", array(":id" => $systemID));
                            $msg = "Command nodes decloaking for **{$systemName}**";
                            break;
                    }

                    if($msg) {
                        $channel = \Discord\Parts\Channel\Channel::find($this->toDiscordChannel);
                        $channel->sendMessage($msg);
                    }

                    // Find the maxID so we don't output this message again in the future
                    $this->maxID = max($notificationID, $this->maxID);
                    $this->newestNotificationID = $this->maxID;
                    $this->storage->set("newestNotificationID", $this->maxID);
                }
            }
        } catch (Exception $e) {
            $this->log->debug("Error: " . $e->getMessage());
        }
    }

    /**
     * @param $keyID
     * @param $vCode
     * @param $characterID
     * @param $notificationID
     * @return mixed
     */
    private function getNotificationText($keyID, $vCode, $characterID, $notificationID)
    {
        $url = "https://api.eveonline.com/char/NotificationTexts.xml.aspx?keyID={$keyID}&vCode={$vCode}&characterID={$characterID}&IDs={$notificationID}";
        $data = json_decode(json_encode(simplexml_load_string($this->curl->getData($url), "SimpleXMLElement", LIBXML_NOCDATA)), true);
        $data = $data["result"]["rowset"]["row"];

        return $data;
    }

    /**
     * @param $type
     * @param $typeID
     * @return mixed
     */
    private function apiData($type, $typeID) {
        $downloadFrom = "";

        switch($type) {
            case "char":
                $downloadFrom = $this->charApi;
                break;

            case "corp":
                $downloadFrom = $this->corpApi;
                break;

            case "alli":
                $downloadFrom = $this->alliApi;
                break;
        }
        return json_decode($this->curl->getData($downloadFrom . $typeID . "/"), true);
    }
}
