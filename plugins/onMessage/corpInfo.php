<?php

class corpInfo
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

            $url = "http://rena.karbowiak.dk/api/search/corporation/{$messageString}/";
            $data = @json_decode(downloadData($url), true)["corporation"];

            if(empty($data))
                return $this->discord->api("channel")->messages()->create($channelID, "**Error:** no results was returned.");

            if(count($data) > 1) {
                $results = array();
                foreach($data as $corp)
                    $results[] = $corp["corporationName"];

                return $this->discord->api("channel")->messages()->create($channelID, "**Error:** more than one result was returned: " . implode(", ", $results));
            }

            // Get stats
            $corporationID = $data[0]["corporationID"];
            $statsURL = "https://beta.eve-kill.net/api/corpInfo/corporationID/" . urlencode($corporationID) ."/";
            $stats = json_decode(downloadData($statsURL), true);

            if(empty($stats))
                return $this->discord->api("channel")->messages()->create($channelID, "**Error:** no data available");

            $corporationName = @$stats["corporationName"];
            $allianceName = isset($stats["allianceName"]) ? $stats["allianceName"] : "None";
            $factionName = isset($stats["factionName"]) ? $stats["factionName"] : "None";
            $ceoName = @$stats["ceoName"];
            $homeStation = @$stats["stationName"];
            $taxRate = @$stats["taxRate"];
            $corporationActiveArea = @$stats["corporationActiveArea"];
            $allianceActiveArea = @$stats["allianceActiveArea"];
            $lifeTimeKills = @$stats["lifeTimeKills"];
            $lifeTimeLosses = @$stats["lifeTimeLosses"];
            $memberCount = @$stats["memberArrayCount"];
            $superCaps = @count($stats["superCaps"]);
            $ePeenSize = @$stats["ePeenSize"];
            $url = "https://beta.eve-kill.net/corporation/" . @$stats["corporationID"] . "/";


            $msg = "```corporationName: {$corporationName}
allianceName: {$allianceName}
factionName: {$factionName}
ceoName: {$ceoName}
homeStation: {$homeStation}
taxRate: {$taxRate}
corporationActiveArea: {$corporationActiveArea}
allianceActiveArea: {$allianceActiveArea}
lifeTimeKills: {$lifeTimeKills}
lifeTimeLosses: {$lifeTimeLosses}
memberCount: {$memberCount}
superCaps: {$superCaps}
ePeenSize: {$ePeenSize}
```
For more info, visit: $url";

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
            "name" => "corp",
            "trigger" => array("!corp"),
            "information" => "Returns basic data about a corporation from projectRena (new EVE-KILL)"
        );
    }

        /**
         * @param $msgData
         */
        function onMessageAdmin($msgData)
        {
        }

}
