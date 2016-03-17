<?php

use Sluggard\SluggardApp;

/**
 * Class fileReader
 */
class fileReader {
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

    private $channelConfig;
    private $lastCheck = 0;
    private $db;

    /**
     * fileReader constructor.
     * @param $discord
     * @param SluggardApp $app
     */
    public function __construct(&$discord, SluggardApp &$app) {
        $this->app = $app;
        $this->config = $app->config;
        $this->discord = $discord;
        $this->log = $app->log;
        $this->sluggardDB = $app->sluggarddata;
        $this->ccpDB = $app->ccpdata;
        $this->curl = $app->curl;
        $this->storage = $app->storage;
        $this->trigger = $app->triggercommand;
        $this->channelConfig = $this->config->getAll("filereader")["channelconfig"];
        $this->db = $this->config->getAll("filereader")["db"];
        if(!is_file($this->db))
            touch($this->db);
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
        if (filemtime($this->db) >= $this->lastCheck) {
            $data = file($this->db);
            if ($data) {
                $message = "";
                foreach ($data as $row) {
                    $row = str_replace("\n", "", str_replace("\r", "", str_replace("^@", "", $row)));
                    if ($row == "" || $row == " ")
                        continue;

                    $message .= $row . " | ";
                    usleep(300000);
                }

                // Remove |  from the line or whatever else is at the last two characters in the string
                $message = trim(substr($message, 0, -2));

                $defaultID = 0;
                foreach($this->channelConfig as $chanName => $chanConfig) {
                    // If a channel is marked as default (usually the first on the list) we populate defaultID here, just to make sure..
                    if($chanConfig["default"] == true)
                        $defaultID = $chanConfig["channelID"];

                    // Search for a channel where the search string matches the actual message
                    if(stristr($message, $chanConfig["searchString"])) {
                        $message = $chanConfig["textStringPrepend"] . " " . $message . " " . $chanConfig["textStringAppend"];
                        $channelID = $chanConfig["channelID"];
                    }
                    elseif($chanConfig["searchString"] == false) { // If no match was found, and searchString is false, just use that
                        $message = $chanConfig["textStringPrepend"] . " " . $message . " " .$chanConfig["textStringAppend"];
                        $channelID = $chanConfig["channelID"];
                    }
                    else { // If something fucked up, we'll just go this route..
                        $channelID = isset($defaultID) ? $defaultID : $chanConfig["channelID"]; // If default ID isn't set, then we just pick whatever we can..
                    }
                }

                // Get the Discord Channel Object
                // Send Message to Channel Object
                $channel = \Discord\Parts\Channel\Channel::find(isset($channelID) ? $channelID : $defaultID);
                $channel->sendMessage($message);
            }

            $h = fopen($this->db, "w+");
            fclose($h);
            chmod($this->db, 0777);
            $data = null;
            $h = null;
        }
        clearstatcache();
        $this->lastCheck = time();
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
            "name" => "",
            "trigger" => array(""),
            "information" => "",
            "timerFrequency" => 1
        );
    }
}