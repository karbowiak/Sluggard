<?php

namespace Sluggard\Lib;


use DateTime;
use Sluggard\SluggardApp;

class logFile
{
    /**
     * @var SluggardApp
     */
    private $app;
    /**
     * @var \Sluggard\Models\SluggardData
     */
    private $db;

    /**
     * Storage constructor.
     * @param SluggardApp $app
     */
    function __construct(SluggardApp &$app) {
        $this->app = $app;
        $this->db = $app->sluggarddata;
    }
    
    public function writeToLog($msgData) {
        $message = $msgData->message->message;
        $receivedTime = $msgData->message->timestamp;
        $date = DateTime::createFromFormat("Y-m-d H:i:s", $receivedTime);
        $logTime = $date->format("Y-m-d-H-i-s");
        $logFileTime = $date->format("dmY");
        $username = $msgData->message->from;
        $userID = $msgData->message->fromID;
        $channelName = $msgData->channel->name;
        $guildName = $msgData->guild->name;


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
    
}