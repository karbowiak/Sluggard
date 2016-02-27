<?php

class eveStatus
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

            $crestData = json_decode(downloadData("https://public-crest.eveonline.com/"), true);

            $tqStatus = isset($crestData["serviceStatus"]["eve"]) ? $crestData["serviceStatus"]["eve"] : "offline";
            $tqOnline = (int) $crestData["userCounts"]["eve"];

            $msg = "**TQ Status:** {$tqStatus} with {$tqOnline} users online.";
            $this->logger->info("Sending eve status info to {$channelName} on {$guildName}");
            $this->discord->api("channel")->messages()->create($channelID, $msg);
        }
    }

    /**
     * @return array
     */
    function information()
    {
        return array(
            "name" => "tq",
            "trigger" => array("!tq", "!status"),
            "information" => "Shows the current status of Tranquility"
        );
    }

    /**
     * @param $msgData
     */
    function onMessageAdmin($msgData)
    {
    }

}
