<?php

use Sluggard\SluggardApp;

class logFileGenerator {
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
     * @param $discord
     * @param SluggardApp $app
     */
    public function __construct($discord, SluggardApp &$app) {
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
        $receivedTime = $msgData->message->timestamp;
        $date = DateTime::createFromFormat("Y-m-d H:i:s", $receivedTime);
        $logTime = $date->format("Y-m-d-H-i-s");
        $logFileTime = $date->format("dmY");
        $username = $msgData->message->from;
        $userID = $msgData->message->fromID;
        $channelName = $msgData->channel->name;
        $guildName = $msgData->guild->name;

        if(isset($data["trigger"])) {
            $this->log->info("Sending log stats info to {$username} in {$channelName} on {$guildName}");
            $msg = "To view the log stats for this channel, look at: http://pisg.karbowiak.dk/" . urlencode(urlencode($guildName) . "." . urlencode($channelName)) . ".html";
            $msgData->user->reply($msg);
        }

        if(!file_exists(BASEDIR . "/logs/" . urlencode($guildName)))
            mkdir(BASEDIR . "/logs/" . urlencode($guildName));

        $logFile = BASEDIR . "/logs/" . urlencode($guildName) . "/" . urlencode($channelName) . ".{$logFileTime}.log";

        if(!is_file($logFile)) {
            touch($logFile);
            chmod($logFile, 0777);
            // Create a PISG config entry for this channel, if it doesn't exist
            $this->createConfigEntry($guildName, $channelName);
        }

        // The Log Format is following the log format for PsyBNC, according to http://pisg.sourceforge.net/docs/pisg-formats.html#_psybnc
        $logString = "{$logTime}:#{$channelName}::{$username}!{$username}@{$userID} PRIVMSG #{$channelName} :{$message}\r\n";

        file_put_contents($logFile, $logString, FILE_APPEND);
    }

    private function createConfigEntry($guildName, $channelName) {
        $pisgFile = BASEDIR . "/cache/pisg.cfg";

        // If the pisgFile doesn't exist, we'll create it!
        if(!is_file($pisgFile)) {
            touch($pisgFile);
            chmod($pisgFile, 0777);
            $cacheDir = BASEDIR . "/cache/pisg/";
            if(!file_exists($cacheDir))
                mkdir($cacheDir);

            $pisgCache = "<set CacheDir=\"{$cacheDir}\">\n";
            file_put_contents($pisgFile, $pisgCache);
        }

        // Check if it already exists
        $pisgData = file_get_contents($pisgFile);

        // If the channelName with a # infront isn't in the list, we'll accept it as if it didn't already exist
        if(!stristr($pisgData, "#" . urlencode($channelName) . "-" . urlencode($guildName))) {
            $logDir = BASEDIR . "/logs/" . urlencode($guildName);

            // Hardcode, yeaaaaaaaa... *shades*
            $outputFile = "/storage/www/pisg.karbowiak.dk/" . urlencode($guildName) . "." . urlencode($channelName) . ".html";

            // The pisgConfig for this channel
            $config = "<channel=\"#". urlencode($channelName) . "-" . urlencode($guildName) ."\">\n";
            $config .= "Network = \"Discord\"\n";
            $config .= "LogDir = \"{$logDir}\"\n";
            $config .= "Format = \"psybnc\"\n";
            $config .= "Maintainer = \"Karbowiak\"\n";
            $config .= "OutputFile = \"{$outputFile}\"\n";
            $config .= "</channel>\n\n";

            // Touch the output file before PISG runs it, and chmod it..
            touch($outputFile);
            chmod($outputFile, 0777);

            // Put the config data into the pisg config
            file_put_contents($pisgFile, $config, FILE_APPEND);
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
            "name" => "log",
            "trigger" => array("!log"),
            "information" => "Gives you a link for this channels logFile stats, generated by PISG",
            "timerFrequency" => 0
        );
    }
}