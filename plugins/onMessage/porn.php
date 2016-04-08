<?php

use Sluggard\SluggardApp;

class porn {
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
            $channelIDs = array();
            foreach($this->channelLimit[get_class($this)] as $c)
                $channelIDs[] = "<#" . $c . "> ";

            $channelsAllowed = implode(" ", $channelIDs);
            $msg = "**Error:** this plugin only works in {$channelsAllowed}";
            return $msgData->user->reply($msg);
        }

        if (isset($data["trigger"])) {
            $urls = array();

            // If it doesn't exist, we'll just make it an empty string..
            if(!isset($data["messageArray"][0]))
                $data["messageArray"][0] = "";

            switch($data["messageArray"][0]) {
                case "redheads":
                case "redhead":
                case "red":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/redheads/time/all/",
                        "https://api.imgur.com/3/gallery/r/ginger/time/all/",
                        "https://api.imgur.com/3/gallery/r/FireCrotch/time/all/"
                    );

                    break;

                case "blondes":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/blondes/time/all/"
                    );
                    break;

                case "asians":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/AsiansGoneWild/time/all/"
                    );
                    break;

                case "gonewild":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/gonewild/time/all/"
                    );
                    break;

                case "realgirls":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/realgirls/time/all/"
                    );
                    break;

                case "palegirls":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/palegirls/time/all/"
                    );
                    break;

                case "gif":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/NSFW_GIF/time/all/"
                    );
                    break;

                case "lesbians":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/lesbians/time/all/"
                    );
                    break;

                case "tattoos":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/Hotchickswithtattoos/time/all/"
                    );
                    break;

                case "mgw":
                case "militarygonewild":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/MilitaryGoneWild/time/all/"
                    );
                    break;

                case "amateur":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/AmateurArchives/time/all/"
                    );
                    break;

                case "college":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/collegesluts/time/all/"
                    );
                break;

                case "bondage":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/bondage/time/all/"
                    );
                    break;

                case "milf":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/milf/time/all/"
                    );
                break;

                case "freckles":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/FreckledGirls/time/all/"
                    );
                    break;
                case "cosplay":
                    $urls = array("https://api.imgur.com/3/gallery/r/cosplay/time/all/");
                    break;
                case "boobs":
                    $urls = array("https://api.imgur.com/3/gallery/r/boobs/time/all/");
                    break;
                case "ass":
                    $urls = array("https://api.imgur.com/3/gallery/r/ass/time/all/");
                    break;

                default:
                    $msg = "No endpoint selected. Currently available are: redheads, blondes, asians, gonewild, realgirls, palegirls, gif, lesbians, tattoos, mgw/militarygonewild, amateur, college, bondage, milf, freckles, boobs, ass and cosplay";
                    $msgData->user->reply($msg);
                    break;
            }

            if(!empty($urls)) {
                // Select a random url
                $url = $urls[array_rand($urls)];
                $clientID = $this->config->get("clientID", "imgur");
                $headers = array();
                $headers[] = "Content-type: application/json";
                $headers[] = "Authorization: Client-ID {$clientID}";
                $data = $this->curl->getData($url, $headers);
                
                
                if($data) {
                    $json = json_decode($data, true)["data"];
                    $count = count($json);

                    $img = $json[array_rand($json)];

                    // Get gifv over gif, if it's infact a gif gallery
                    //$imageURL = isset($img["gifv"]) ? $img["gifv"] : $img["link"];
                    $imageURL = $img["link"]; // gifv doesn't embed properly in discord, yet..

                    $message = "**Title:** {$img["title"]} | **Section:** {$img["section"]} | **url:** {$imageURL}";
                    $msgData->user->reply($message);
                }
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
            "name" => "porn",
            "trigger" => array("!porn", "!pron", "!pr0n"),
            "information" => "Returns an image, gif or mp4 from a selection of Imgur / Tumblr / What have you sites",
            "timerFrequency" => 0,
            "channelLimit" => $this->channelLimit
        );
    }
}