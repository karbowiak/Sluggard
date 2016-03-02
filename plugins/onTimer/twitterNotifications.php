<?php

use Sluggard\SluggardApp;

/**
 * Class twitterNotifications
 */
class twitterNotifications
{
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

    private $twitter;
    private $lastID;
    private $channelID;
    private $maxID;

    /**
     * twitterNotifications constructor.
     * @param $discord
     * @param SluggardApp $app
     */
    public function __construct($discord, SluggardApp $app)
    {
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
        $this->twitter = new Twitter($this->config->get("consumerKey", "twitter"), $this->config->get("consumerSecret", "twitter"), $this->config->get("accessToken", "twitter"), $this->config->get("accessTokenSecret", "twitter"));
        $this->maxID = 0;
        $this->channelID = $this->config->get("channelID", "twitteroutput");
    }

    /**
     * When a message arrives that contains a trigger, this is started
     *
     * @param $msgData
     */
    public function onMessage($msgData)
    {

    }

    /**
     * When the bot starts, this is started
     */
    public function onStart()
    {

    }

    /**
     * When the bot does a tick (every second), this is started
     */
    public function onTick()
    {

    }

    /**
     * When the bot's tick hits a specified time, this is started
     *
     * Runtime is defined in $this->information(), timerFrequency
     */
    public function onTimer()
    {
        $continue = false;
        $data = array();

        // Fetch the last 5 twitter replies and/or searches
        try {
            $data = $this->twitter->load(Twitter::ME_AND_FRIENDS, 5);
            foreach ($data as $message) {
                $text = (array)$message->text;
                $createdAt = (array)$message->created_at;
                $postedBy = (array)$message->user->name;
                $screenName = (array)$message->user->screen_name;
                $id = (int)$message->id;
                $this->lastID = $this->storage->get("twitterLatestID"); // get the last posted ID

                if ($id <= $this->lastID)
                    continue;

                $this->maxID = max($id, $this->maxID);

                $url = "https://twitter.com/" . $screenName[0] . "/status/" . $id;
                $message = array("message" => $text[0], "postedAt" => $createdAt[0], "postedBy" => $postedBy[0], "screenName" => $screenName[0], "url" => $url . $id[0]);
                $msg = "**@" . $screenName[0] . "** (" . $message["postedBy"] . ") / " . htmlspecialchars_decode($message["message"]);
                $messages[$id] = $msg;

                $continue = true;

                if (sizeof($data))
                    $this->storage->set("twitterLatestID", $this->maxID);
            }
        } catch (Exception $e) {
            //$this->log->err("Twitter Error: " . $e->getMessage()); // Don't show there was an error, it's most likely just a rate limit
        }

        if ($continue == true) {
            ksort($messages);

            $channel = \Discord\Parts\Channel\Channel::find($this->channelID);
            foreach ($messages as $id => $msg) {
                $channel->sendMessage($msg);
                sleep(1); // Lets sleep for a second, so we don't rage spam
            }
        }
    }

    /**
     * @return array
     *
     * name: is the name of the script
     * trigger: is an array of triggers that can trigger this plugin
     * information: is a short description of the plugin
     * timerFrequency: if this were an onTimer script, it would execute every x seconds, as defined by timerFrequency
     */
    public function information()
    {
        return array(
            "name" => "twitterNotifications",
            "trigger" => array(""),
            "information" => "",
            "timerFrequency" => 60
        );
    }
}