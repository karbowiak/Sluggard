<?php

/**
 * Class time
 */
class time
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
            $date = date("d-m-Y");
            $fullDate = date("Y-m-d H:i:s");
            $datetime = new DateTime($fullDate);
            $et = $datetime->setTimezone(new DateTimeZone("America/New_York"));
            $et = $et->format("H:i:s");
            $pt = $datetime->setTimezone(new DateTimeZone("America/Los_Angeles"));
            $pt = $pt->format("H:i:s");
            $utc = $datetime->setTimezone(new DateTimeZone("UTC"));
            $utc = $utc->format("H:i:s");
            $cet = $datetime->setTimezone(new DateTimeZone("Europe/Copenhagen"));
            $cet = $cet->format("H:i:s");
            $msk = $datetime->setTimezone(new DateTimeZone("Europe/Moscow"));
            $msk = $msk->format("H:i:s");
            $aest = $datetime->setTimezone(new DateTimeZone("Australia/Sydney"));
            $aest = $aest->format("H:i:s");

            $this->logger->info("Sending time info to {$channelName} on {$guildName}");
            $this->discord->api("channel")->messages()->create($channelID, "**EVE Time:** {$utc} / **EVE Date:** {$date} / **PT:** {$pt} / **ET:** {$et} / **CET:** {$cet} / **MSK:** {$msk} / **AEST:** {$aest}");
        }
    }

    /**
     * @return array
     */
    function information()
    {
        return array(
            "name" => "time",
            "trigger" => array("!time"),
            "information" => "This shows the time for various timezones compared to EVE Time. Example: **!time**"
        );
    }

    /**
     * @param $msgData
     */
    function onMessageAdmin($msgData)
    {
    }

}