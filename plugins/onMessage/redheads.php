<?php

use Sluggard\SluggardApp;

class redheads {
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
        $this->channelLimit = $app->config->getAll("channellimit");
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
            $url = "http://stunningredheads.tumblr.com/archive";
            $data = $this->curl->getData($url);
            preg_match_all("/http:\/\/(.*).media.tumblr.com\/(.*)\/tumblr(.*).jpg/", $data, $matches);

            $images = array();
            foreach($matches[0] as $img)
                $images[] = str_replace("_250", "_1280", $img);

            if(!empty($images)) {
                $msg = $images[mt_rand(0, count($images))];

                if(empty($msg))
                    $msg = $images[mt_rand(0, count($images))];

                $msgData->user->reply($msg);
            } else {
                $msg = "**Error:** couldn't retrieve an image from Stunning Redheads, you gotta go find them yourself at {$url}";
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
            "name" => "redheads",
            "trigger" => array("!red", "!redheads"),
            "information" => "Returns an image from Stunning Readheads on Tumblr",
            "timerFrequency" => 0,
            "channelLimit" => $this->channelLimit
        );
    }
}