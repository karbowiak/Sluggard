<?php

class charInfo
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
    }

    /**
     *
     */
    function tick()
    {
    }

    /**
     * @param $msgData
     */
    function onMessage($msgData)
    {
        $message = $msgData["message"]["message"];
        $channelName = $msgData["channel"]["name"];
        $guildName = $msgData["guild"]["name"];
        $channelID = $msgData["message"]["channelID"];

        $data = command($message, $this->information()["trigger"]);
        if (isset($data["trigger"])) {
            $trigger = $data["trigger"];
            $messageArray = $data["messageArray"];
            $messageString = $data["messageString"];

            // Most EVE players on Discord use their ingame name, so lets support @highlights
            $messageString = stristr($data["messageString"], "@") ? str_replace("<@", "", str_replace(">", "", $data["messageString"])) : $data["messageString"];
            if(is_numeric($messageString)) // The person used @highlighting, so now we got a discord id, lets map that to a name
                $messageString = dbQueryField("SELECT name FROM usersSeen WHERE id = :id", "name", array(":id" => $messageString));

            $url = "http://rena.karbowiak.dk/api/search/character/{$messageString}/";
            $data = @json_decode(downloadData($url), true)["character"];

            if(empty($data))
                return $this->discord->api("channel")->messages()->create($channelID, "**Error:** error, no results was returned.");

            if(count($data) > 1) {
                $results = array();
                foreach($data as $char)
                    $results[] = $char["characterName"];

                return $this->discord->api("channel")->messages()->create($channelID, "**Error:** more than one result was returned: " . implode(", ", $results));
            }

            // Get stats
            $characterID = $data[0]["characterID"];
            $statsURL = "https://beta.eve-kill.net/api/charInfo/characterID/{$characterID}/";
            $stats = json_decode(downloadData($statsURL), true);

            $characterName = @$stats["characterName"];
            $corporationName = @$stats["corporationName"];
            $allianceName = @$stats["allianceName"];
            $securityStatus = @$stats["securityStatus"];
            $lastSeenSystem = @$stats["lastSeenSystem"];
            $lastSeenRegion = @$stats["lastSeenRegion"];
            $lastSeenShip = @$stats["lastSeenShip"];
            $corporationActiveArea = @$stats["corporationActiveArea"];
            $allianceActiveArea = @$stats["allianceActiveArea"];
            $soloKills = @$stats["soloKills"];
            $blobKills = @$stats["blobKills"];
            $lifeTimeKills = @$stats["lifeTimeKills"];
            $lifeTimeLosses = @$stats["lifeTimeLosses"];
            $amountOfSoloPVPer = @$stats["percentageSoloPVPer"];
            $epeenSize = @$stats["ePeenSize"];
            $facepalms = @$stats["facepalms"];


            $msg = "```characterName: {$characterName}
corporationName: {$corporationName}
allianceName: {$allianceName}
securityStatus: {$securityStatus}
lastSeenSystem: {$lastSeenSystem}
lastSeenRegion: {$lastSeenRegion}
lastSeenShip: {$lastSeenShip}
corporationActiveArea: {$corporationActiveArea}
allianceActiveArea: {$allianceActiveArea}
soloKills: {$soloKills}
blobKills: {$blobKills}
lifeTimeKills: {$lifeTimeKills}
lifeTimeLosses: {$lifeTimeLosses}
percentageSoloPVPer: {$amountOfSoloPVPer}
ePeenSize: {$epeenSize}
facepalms: {$facepalms}
```";

            if(empty($characterName))
                $msg = "**Error:** no data available";

            $this->logger->info("Sending character info to {$channelName} on {$guildName}");
            $this->discord->api("channel")->messages()->create($channelID, $msg);
        }
    }

    /**
     * @return array
     */
    function information()
    {
        return array(
            "name" => "charInfo",
            "trigger" => array("!char"),
            "information" => "Returns basic data about a character from projectRena (new EVE-KILL)"
        );
    }

        /**
         * @param $msgData
         */
        function onMessageAdmin($msgData)
        {
        }

}
