<?php

class time
{
    var $config;
    var $discord;

    function init($config, $discord)
    {
        $this->config = $config;
        $this->discord = $discord;
    }

    function information()
    {
        return array(
            "name" => "time",
            "trigger" => array("time"),
            "information" => "This shows the time for various timezones compared to EVE Time"
        );
    }

    function tick()
    {

    }

    function onMessage($message, $channelID)
    {
        if (stringStartsWith($message, "!time")) {
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
            $this->discord->api("channel")->messages()->create($channelID, "**EVE Time:** {$utc} / **EVE Date:** {$date} / **PT:** {$pt} / **ET:** {$et} / **CET:** {$cet} / **MSK:** {$msk} / **AEST:** {$aest}");
        }
    }
}