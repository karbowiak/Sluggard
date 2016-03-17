<?php

use Sluggard\SluggardApp;

class eveFitting {
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

        if (isset($data["trigger"])) {
            $channelName = $msgData->channel->name;
            $guildName = $msgData->guild->name;
            $guildID = $msgData->guild->id;

            $switch = $data["messageArray"][0];
            switch($switch) {
                case "save":
                    $fittingURL = $data["messageArray"][1];

                    unset($data["messageArray"][0]);
                    unset($data["messageArray"][1]);
                    $fittingName = implode(" ", $data["messageArray"]);

                    $this->sluggardDB->execute("REPLACE INTO fittings (guildID, fittingName, fittingURL) VALUES (:guildID, :fittingName, :fittingURL)", array(":guildID" => $guildID, ":fittingName" => $fittingName, ":fittingURL" => $fittingURL));

                    $this->log->info("Saved fitting ({$fittingName}) on {$guildName}");
                    $msg = "Fitting saved, you can now call upon it by using !fit {$fittingName}";
                    $msgData->user->reply($msg);
                    break;

                case "delete":
                    unset($data["messageArray"][0]);
                    $fittingName = implode(" ", $data["messageArray"]);

                    if(strlen($fittingName) > 1) {
                        $fittingData = $this->sluggardDB->queryField("SELECT fittingURL FROM fittings WHERE fittingName = :fittingName AND guildID = :guildID", "fittingURL", array(":fittingName" => $fittingName, ":guildID" => $guildID));

                        if ($fittingData) {
                            $this->sluggardDB->execute("DELETE FROM fittings WHERE fittingName = :fittingName AND guildID = :guildID", array(":fittingName" => $fittingName, ":guildID" => $guildID));
                            $msg = "Fitting {$fittingName} was deleted";
                        } else {
                            $msg = "Fitting {$fittingName} not found for this server";
                        }
                    } else {
                        $msg = "Error with your search";
                    }
                    $msgData->user->reply($msg);
                    break;

                default:
                    $search = $data["messageString"];

                    if(strlen($search) > 1) {
                        $fittingData = $this->sluggardDB->queryField("SELECT fittingURL FROM fittings WHERE fittingName = :fittingName AND guildID = :guildID", "fittingURL", array(":fittingName" => $search, ":guildID" => $guildID));

                        if ($fittingData) {
                            $msg = "Fitting {$search} can be seen here: $fittingData";
                        } else {
                            $msg = "Fitting {$search} not found for this server";
                        }
                    } else {
                        $msg = "Error with your search";
                    }

                    $msgData->user->reply($msg);
                    break;
            }
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
            "name" => "fit",
            "trigger" => array("!fit"),
            "information" => "Shows and/or creates fitting links to use pr. server.",
            "timerFrequency" => 0
        );
    }
}