<?php

class notifications
{
    /**
     * @var
     */
    var $config;
    /**
     * @var
     */
    var $discord;
    /**
     * @var
     */
    var $logger;

    var $nextCheck;
    var $keys;
    var $keyCount;
    var $toDiscordChannel;
    var $newestNotificationID;
    var $maxID;
    /**
     * @param $config
     * @param $discord
     * @param $logger
     */
    function init($config, $discord, $logger)
    {
        $this->config = $config;
        $this->discord = $discord;
        $this->logger = $logger;
        $this->toDiscordChannel = 149918425018400768; // Intel channel
        $this->newestNotificationID = getPermCache("newestNotificationID");
        $this->maxID = 0;
        $this->keyCount = count($config["eve"]["apiKeys"]);
        $this->keys = $config["eve"]["apiKeys"];
        $this->nextCheck = 0;
    }

    /**
     *
     */
    function tick()
    {
        if($this->nextCheck <= time())
        {
            $check = true;
            foreach ($this->keys as $keyOwner => $api) {
                if($check == false)
                    continue;

                $keyID = $api["keyID"];
                $vCode = $api["vCode"];
                $characterID = $api["characterID"];
                $lastChecked = getPermCache("notificationCheck{$keyID}{$keyOwner}{$characterID}");

                if($lastChecked <= time()) {
                    $this->logger->info("Checking API Key {$keyID} belonging to {$keyOwner} for new notifications");
                    $this->getNotifications($keyID, $vCode, $characterID);
                    $check = false;
                }

                setPermCache("notificationCheck{$keyID}{$keyOwner}{$characterID}", time() + 1800); // Reschedule it's check for 30minutes from now
                $this->nextCheck = time() + (1800 / $this->keyCount); // Next check is in 1800 seconds divided by the amount of keys
            }
        }
    }

    function getNotifications($keyID, $vCode, $characterID)
    {
        // Ignore notifications from these douchebags..
        $ignoreNames = array("CONCORD");
        $updateMaxID = false;
        $url = "https://api.eveonline.com/char/Notifications.xml.aspx?keyID={$keyID}&vCode={$vCode}&characterID={$characterID}";
        $data = json_decode(json_encode(simplexml_load_string(downloadData($url), "SimpleXMLElement", LIBXML_NOCDATA)), true);
        $data = $data["result"]["rowset"]["row"];

        $fixedData = array();
        foreach($data as $getFuckedCCP)
            $fixedData[] = $getFuckedCCP["@attributes"];

        foreach($fixedData as $notification) {
            $notificationID = $notification["notificationID"];
            $typeID = $notification["typeID"];
            $senderID = $notification["senderID"];
            $senderName = $notification["senderName"];
            $sentDate = $notification["sentDate"];
            $read = $notification["read"];

            // If the senderName is in the list of ignores names, then continue and ignore it..
            if(in_array($senderName, $ignoreNames))
                continue;

            if($notificationID > $this->newestNotificationID) {
                $notificationString = explode("\n", $this->getNotificationText($keyID, $vCode, $characterID, $notificationID));

                // Seriously, get fucked CCP
                switch($typeID) {
                    case 8: // Alliance war invalidated by CONCORD
                        $msg = $notificationString;
                        break;

                    case 10: // Bill issued to corp/alliance
                        $msg = $notificationString;
                        break;

                    case 13: // Bill issued to corp/alliance has been paid
                        $msg = $notificationString;
                        break;

                    case 16: // New member application
                        $msg = $notificationString;
                        break;

                    case 19: // Corp Tax rate Changed
                        $msg = $notificationString;
                        break;

                    case 45: // Alliance anchoring alert
                        $msg = $notificationString;
                        break;

                    case 52: // Corp member clones moved to new station
                        $msg = $notificationString;
                        break;

                    case 75: // POS / POS Module under attack
                        $msg = $notificationString;
                        break;

                    case 76: // Tower resource alert
                        $msg = $notificationString;
                        break;

                    case 77: // Station service being attacked
                        $aggressorCorpID = trim(explode(": ", $notificationString[0])[1]);
                        $aggressorCorpName = dbQueryField("SELECT corporationName FROM corporations WHERE corporationID = :id", "corporationName", array(":id" => $aggressorCorpID), "ccp");
                        $aggressorID = trim(explode(": ", $notificationString[1])[1]);
                        $aggressorCharacterName = dbQueryField("SELECT characterName FROM characters WHERE characterID = :id", "characterName", array(":id" => $aggressorID), "ccp");
                        $shieldValue = trim(explode(": ", $notificationString[2])[1]);
                        $systemID = trim(explode(": ", $notificationString[3])[1]);
                        $systemName = dbQueryField("SELECT solarSystemName FROM mapSolarSystems WHERE solarSystemID = :id", "solarSystemName", array(":id" => $systemID), "ccp");
                        $stationID = trim(explode(": ", $notificationString[4])[1]);
                        $stationName = dbQueryField("SELECT itemName FROM mapAllCelestials WHERE itemID = :id", "itemName", array(":id" => $stationID), "ccp");
                        $typeID = trim(explode(": ", $notificationString[5])[1]);
                        $typeName = dbQueryField("SELECT typeName FROM invTypes WHERE typeID = :id", "typeName", array(":id" => $typeID), "ccp");

                        $msg = "Station service is being attacked in **{$systemName} ({$stationName} / {$typeName})** by {$aggressorCharacterName} / {$aggressorCorpName}. Shield Status: {$shieldValue}";
                        break;

                    case 87: // SBU being attacked
                        $aggressorAllianceID = trim(explode(": ", $notificationString[0])[1]);
                        $aggressorAllianceName = dbQueryField("SELECT allianceName FROM alliances WHERE allianceID = :id", "allianceName", array(":id" => $aggressorAllianceID), "ccp");
                        $aggressorCorpID = trim(explode(": ", $notificationString[1])[1]);
                        $aggressorCorpName = dbQueryField("SELECT corporationName FROM corporations WHERE corporationID = :id", "corporationName", array(":id" => $aggressorCorpID), "ccp");
                        $aggressorID = trim(explode(": ", $notificationString[2])[1]);
                        $aggressorCharacterName = dbQueryField("SELECT characterName FROM characters WHERE characterID = :id", "characterName", array(":id" => $aggressorID), "ccp");
                        $armorValue = trim(explode(": ", $notificationString[3])[1]);
                        $hullValue = trim(explode(": ", $notificationString[4])[1]);
                        $shieldValue = trim(explode(": ", $notificationString[5])[1]);
                        $solarSystemID = trim(explode(": ", $notificationString[6])[1]);
                        $systemName = dbQueryField("SELECT solarSystemName FROM mapSolarSystems WHERE solarSystemID = :id", "solarSystemName", array(":id" => $solarSystemID), "ccp");

                        $msg = "SBU under attack in **{$systemName}** by {$aggressorCharacterName} ({$aggressorCorpName} / {$aggressorAllianceName}). Status: Hull: {$hullValue}, Armor: {$armorValue}, Shield: {$shieldValue}";
                        break;

                    case 88: // IHUB is being attacked
                        $aggressorAllianceID = trim(explode(": ", $notificationString[0])[1]);
                        $aggressorAllianceName = dbQueryField("SELECT allianceName FROM alliances WHERE allianceID = :id", "allianceName", array(":id" => $aggressorAllianceID), "ccp");
                        $aggressorCorpID = trim(explode(": ", $notificationString[1])[1]);
                        $aggressorCorpName = dbQueryField("SELECT corporationName FROM corporations WHERE corporationID = :id", "corporationName", array(":id" => $aggressorCorpID), "ccp");
                        $aggressorID = trim(explode(": ", $notificationString[2])[1]);
                        $aggressorCharacterName = dbQueryField("SELECT characterName FROM characters WHERE characterID = :id", "characterName", array(":id" => $aggressorID), "ccp");
                        $armorValue = trim(explode(": ", $notificationString[3])[1]);
                        $hullValue = trim(explode(": ", $notificationString[4])[1]);
                        $shieldValue = trim(explode(": ", $notificationString[5])[1]);
                        $solarSystemID = trim(explode(": ", $notificationString[6])[1]);
                        $systemName = dbQueryField("SELECT solarSystemName FROM mapSolarSystems WHERE solarSystemID = :id", "solarSystemName", array(":id" => $solarSystemID), "ccp");

                        $msg = "IHUB under attack in **{$systemName}** by {$aggressorCharacterName} ({$aggressorCorpName} / {$aggressorAllianceName}). Status: Hull: {$hullValue}, Armor: {$armorValue}, Shield: {$shieldValue}";
                        break;

                    case 93: // Customs office is being attacked
                        $aggressorAllianceID = trim(explode(": ", $notificationString[0])[1]);
                        $aggressorAllianceName = dbQueryField("SELECT allianceName FROM alliances WHERE allianceID = :id", "allianceName", array(":id" => $aggressorAllianceID), "ccp");
                        $aggressorCorpID = trim(explode(": ", $notificationString[1])[1]);
                        $aggressorCorpName = dbQueryField("SELECT corporationName FROM corporations WHERE corporationID = :id", "corporationName", array(":id" => $aggressorCorpID), "ccp");
                        $aggressorID = trim(explode(": ", $notificationString[2])[1]);
                        $aggressorCharacterName = dbQueryField("SELECT characterName FROM characters WHERE characterID = :id", "characterName", array(":id" => $aggressorID), "ccp");

                        $planetID = trim(explode(": ", $notificationString[3])[1]);
                        $planetName = dbQueryField("SELECT itemName FROM mapAllCelestials WHERE itemID = :id", "itemName", array(":id" => $planetID), "ccp");
                        $shieldValue = trim(explode(": ", $notificationString[5])[1]);
                        $solarSystemID = trim(explode(": ", $notificationString[6])[1]);
                        $systemName = dbQueryField("SELECT solarSystemName FROM mapSolarSystems WHERE solarSystemID = :id", "solarSystemName", array(":id" => $solarSystemID), "ccp");
                        $typeID = trim(explode(": ", $notificationString[7])[1]);
                        $typeName = dbQueryField("SELECT typeName FROM invTypes WHERE typeID = :id", "typeName", array(":id" => $typeID), "ccp");

                        $msg = "Customs Office under attack in **{$systemName}** ($planetName) by {$aggressorCharacterName} ({$aggressorCorpName} / {$aggressorAllianceName}). Shield Status: {$shieldValue}";
                        break;

                    case 147: // Entosis has stated
                        $systemID = trim(explode(": ", $notificationString[0])[1]);
                        $systemName = dbQueryField("SELECT solarSystemName FROM mapSolarSystems WHERE solarSystemID = :id", "solarSystemName", array(":id" => $systemID), "ccp");
                        $typeID = trim(explode(": ", $notificationString[1])[1]);
                        $typeName = dbQueryField("SELECT typeName FROM invTypes WHERE typeID = :id", "typeName", array(":id" => $typeID), "ccp");

                        $msg = "Entosis has started in **{$systemName}** on **{$typeName}** (Date: **{$sentDate}**)";
                        break;

                    case 148: // Entosis enabled a module ??????
                        $systemID = trim(explode(": ", $notificationString[0])[1]);
                        $systemName = dbQueryField("SELECT solarSystemName FROM mapSolarSystems WHERE solarSystemID = :id", "solarSystemName", array(":id" => $systemID), "ccp");
                        $typeID = trim(explode(": ", $notificationString[1])[1]);
                        $typeName = dbQueryField("SELECT typeName FROM invTypes WHERE typeID = :id", "typeName", array(":id" => $typeID), "ccp");

                        $msg = "Entosis has enabled a module in **{$systemName}** on **{$typeName}** (Date: **{$sentDate}**)";
                        break;

                    case 149: // Entosis disabled a module
                        $systemID = trim(explode(": ", $notificationString[0])[1]);
                        $systemName = dbQueryField("SELECT solarSystemName FROM mapSolarSystems WHERE solarSystemID = :id", "solarSystemName", array(":id" => $systemID), "ccp");
                        $typeID = trim(explode(": ", $notificationString[1])[1]);
                        $typeName = dbQueryField("SELECT typeName FROM invTypes WHERE typeID = :id", "typeName", array(":id" => $typeID), "ccp");

                        $msg = "Entosis has disabled a module in **{$systemName}** on **{$typeName}** (Date: **{$sentDate}**)";
                        break;

                    default:
                        $msg = "ERROR: Unhandled:" . $notificationString;
                        break;
                }
                $this->discord->api("channel")->messages()->create($this->toDiscordChannel, $msg);
                // Find the maxID so we don't output this message again in the future
                $this->maxID = max($notificationID, $this->maxID);
                $this->newestNotificationID = $notificationID;
                $updateMaxID = true;
            }
        }

        if($updateMaxID)
            setPermCache("newestNotificationID", $this->maxID);
    }

    function getNotificationText($keyID, $vCode, $characterID, $notificationID)
    {
        $url = "https://api.eveonline.com/char/NotificationTexts.xml.aspx?keyID={$keyID}&vCode={$vCode}&characterID={$characterID}&IDs={$notificationID}";
        $data = json_decode(json_encode(simplexml_load_string(downloadData($url), "SimpleXMLElement", LIBXML_NOCDATA)), true);
        $data = $data["result"]["rowset"]["row"];

        return $data;
    }
    /**
     * @param $msgData
     */
    function onMessage($msgData)
    {
    }

    /**
     * @return array
     */
    function information()
    {
        return array(
            "name" => "",
            "trigger" => array(""),
            "information" => ""
        );
    }
}
