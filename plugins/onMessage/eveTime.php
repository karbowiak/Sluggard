<?php

use Sluggard\SluggardApp;

/**
 * Class eveTime
 */
class eveTime
{
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
     * @var \Sluggard\Lib\async
     */
    private $async;
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
     * eveTime constructor.
     * @param $discord
     * @param SluggardApp $app
     */
    public function __construct($discord, SluggardApp $app)
    {
        $this->app = $app;
        $this->config = $app->config;
        $this->discord = $discord;
        $this->log = $app->log;
        $this->async = $app->async;
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
    public function onMessage($msgData)
    {
        $message = $msgData->message->message;
        $data = $this->trigger->trigger($message, $this->information()["trigger"]);

        if (isset($data["trigger"])) {
            $channelName = $msgData->channel->name;
            $guildName = $msgData->guild->name;

            $date = date("d-m-Y");
            $fullDate = date("Y-m-d H:i:s");
            $dateTime = new DateTime($fullDate);

            $et = $dateTime->setTimezone(new DateTimeZone("America/New_York"));
            $et = $et->format("H:i:s");
            $pt = $dateTime->setTimezone(new DateTimeZone("America/Los_Angeles"));
            $pt = $pt->format("H:i:s");
            $utc = $dateTime->setTimezone(new DateTimeZone("UTC"));
            $utc = $utc->format("H:i:s");
            $cet = $dateTime->setTimezone(new DateTimeZone("Europe/Copenhagen"));
            $cet = $cet->format("H:i:s");
            $msk = $dateTime->setTimezone(new DateTimeZone("Europe/Moscow"));
            $msk = $msk->format("H:i:s");
            $aest = $dateTime->setTimezone(new DateTimeZone("Australia/Sydney"));
            $aest = $aest->format("H:i:s");

            $this->log->info("Sending time info to {$channelName} on {$guildName}");
            $msgData->user->reply("**Current EVE Time:** {$utc} / **EVE Date:** {$date} / **PT:** {$pt} / **ET:** {$et} / **CET:** {$cet} / **MSK:** {$msk} / **AEST:** {$aest}");

        }
    }

    /**
     * @return array
     *
     * name: is the name of the script
     * trigger: is an array of triggers that can trigger this plugin
     * information: is a short description of the plugin
     * timerFrequency: if this were an onTimer script, it would execute every x seconds, as defined by timerFrequency
     */
    public function information()
    {
        return array(
            "name" => "time",
            "trigger" => array("!time"),
            "information" => "Shows the current time for various timezones, compared to the current EVE Time. Example: **!time**",
            "timerFrequency" => 0
        );
    }

    /**
     * When the bot starts, this is started
     */
    public function onStart()
    {

    }

    /**
     * When the bot does a tick (every second), this is started
     */
    public function onTick()
    {

    }

    /**
     * When the bot's tick hits a specified time, this is started
     *
     * Runtime is defined in $this->information(), timerFrequency
     */
    public function onTimer()
    {

    }
}