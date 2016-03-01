<?php

use Sluggard\SluggardApp;

/**
 * Class tqStatus
 */
class tqStatus {
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
     * tqStatus constructor.
     * @param $discord
     * @param SluggardApp $app
     */
    public function __construct($discord, SluggardApp $app) {
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
        $crestData = json_decode($this->curl->getData("https://public-crest.eveonline.com/"), true);
        $tqStatus = isset($crestData["serviceStatus"]["eve"]) ? $crestData["serviceStatus"]["eve"] : "offline";
        $tqOnline = (int) $crestData["userCounts"]["eve"];

        // Store the current status in the permanent cache
        $oldStatus = $this->storage->get("eveTQStatus");
        if($tqStatus !== $oldStatus) {
            $msg = "**New TQ Status:** ***{$tqStatus}*** / ***{$tqOnline}*** users online.";
            $this->log->info("TQ Status changed from {$oldStatus} to {$tqStatus}");
            $channel = \Discord\Parts\Channel\Channel::find($this->config->get("channelID", "periodictqstatus"));
            $channel->sendMessage($msg);
        }
        $this->storage->set("eveTQStatus", $tqStatus);
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
            "name" => "tqStatus",
            "trigger" => array(""),
            "information" => "",
            "timerFrequency" => 30
        );
    }
}