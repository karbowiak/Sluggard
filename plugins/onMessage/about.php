<?php

use Sluggard\SluggardApp;

/**
 * Class about
 */
class about {
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
     * about constructor.
     * @param $discord
     * @param SluggardApp $app
     */
    public function __construct($discord, SluggardApp $app) {
        $this->app = $app;
        $this->config = $app->config;
        $this->discord = $discord;
        $this->log = $app->log;
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
        $message = $msgData->message->message;
        $data = $this->trigger->trigger($message, $this->information()["trigger"]);

        if(isset($data["trigger"])) {
            $channelName = $msgData->channel->name;
            $guildName = $msgData->guild->name;
            $startTime = $this->app->startTime;
            $time1 = new DateTime(date("Y-m-d H:i:s", $startTime));
            $time2 = new DateTime(date("Y-m-d H:i:s"));
            $interval = $time1->diff($time2);
            $gitRevision = $this->app->gitrevision->getRevision();

            $msg = "```Hello, i am Sluggard - i am a bot created for EVE Online related Discord servers.
Also, i am the half-brother of Sovereign, atleast in the Blasto 7 movie..

About Me:
Author: Karbowiak (Discord ID: 118440839776174081)
Library: discord-hypertext (PHP)
Current version: " . $gitRevision["short"]. " (Last Update: " . $gitRevision["lastChangeDate"] . ")
Github Repo: https://github.com/karbowiak/Sluggard

Statistics:
Uptime: " . $interval->y . " Year(s), " .$interval->m . " Month(s), " . $interval->d ." Days, ". $interval->h . " Hours, " . $interval->i." Minutes, ".$interval->s." seconds.
Memory Usage: ~" . round(memory_get_usage() / 1024 / 1024, 3) . "MB```";

           $this->log->info("Sending about info to {$channelName} on {$guildName}");
           $msgData->user->reply($msg);
        }
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
            "name" => "about",
            "trigger" => array("!about"),
            "information" => "Shows information about the bot",
            "timerFrequency" => 0
        );
    }
}