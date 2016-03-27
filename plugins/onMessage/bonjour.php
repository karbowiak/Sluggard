<?php

use Sluggard\SluggardApp;

class bonjour {
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
            $url = "http://dites.bonjourmadame.fr/";
            $data = $this->curl->getData($url);
            preg_match("/http:\/\/(.*).media.tumblr.com\/(.*)\/tumblr(.*).jpg/", $data, $matches);

            if(isset($matches[0])) {
                $msg = $matches[0];
                $msgData->user->reply($msg);
            } else {
                $msg = "**Error:** couldn't retrieve boobies from Bonjour Madame, you gotta go find them yourself at {$url}";
                $msgData->user->reply($msg);
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
            "name" => "bonjourmadamme",
            "trigger" => array("!bonjour", "!bonjourmadamme", "!bm"),
            "information" => "Returns the latest image from Bonjour Madamme",
            "timerFrequency" => 0
        );
    }
}