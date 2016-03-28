<?php

use Sluggard\SluggardApp;

class boobs {
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
     * @var
     */
    private $channelLimit;
    
    /**
     * @param $discord
     * @param SluggardApp $app
     */
    public function __construct(\Discord\Discord &$discord, SluggardApp &$app) {
        $this->app = $app;
        $this->config = $app->config;
        $this->discord = $discord;
        $this->log = $app->log;
        $this->sluggardDB = $app->sluggarddata;
        $this->ccpDB = $app->ccpdata;
        $this->curl = $app->curl;
        $this->storage = $app->storage;
        $this->trigger = $app->triggercommand;
        $this->channelLimit = $app->config->getAll("channelLimit");
    }

    /**
     * When a message arrives that contains a trigger, this is started
     *
     * @param $msgData
     * @return mixed
     */
    public function onMessage($msgData) {
        $message = $msgData->message->message;
        $data = $this->trigger->trigger($message, $this->information()["trigger"]);

        // If this channel is not in the allowed channels array for this plugin, we'll just quit
        if(!in_array($msgData->message->channelID, $this->channelLimit[get_class($this)])) {
            $msg = "**Error:** this plugin only works in <#{$msgData->message->channelID}>";
            return $msgData->user->reply($msg);
        }
        
        if (isset($data["trigger"])) {
            $maxID = json_decode($this->curl->getData("http://api.oboobs.ru/boobs/"))[0];
            $boobsID = mt_rand(10000, $maxID->id);

            $msg = "http://media.oboobs.ru/boobs/{$boobsID}.jpg";
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
            "name" => "boobs",
            "trigger" => array("!boobs", "!titties", "!tits", "!boobies"),
            "information" => "Returns a set of boobies from oboobs.ru",
            "timerFrequency" => 0,
            "channelLimit" => $this->channelLimit
        );
    }
}