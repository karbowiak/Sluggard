<?php

use Sluggard\SluggardApp;

/**
 * Class eveStatus
 */
class eveStatus
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
     * eveStatus constructor.
     * @param $discord
     * @param SluggardApp $app
     */
    public function __construct(&$discord, SluggardApp &$app)
    {
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
    public function onMessage($msgData)
    {
        $message = $msgData->message->message;
        $data = $this->trigger->trigger($message, $this->information()["trigger"]);

        if (isset($data["trigger"])) {
            $channelName = $msgData->channel->name;
            $guildName = $msgData->guild->name;

            $crestData = json_decode($this->curl->getData("https://public-crest.eveonline.com/"), true);

            $tqStatus = isset($crestData["serviceStatus"]["eve"]) ? $crestData["serviceStatus"]["eve"] : "offline";
            $tqOnline = (int)$crestData["userCounts"]["eve"];

            $msg = "**TQ Status:** {$tqStatus} with {$tqOnline} users online.";
            $this->log->info("Sending eveStatus info to {$channelName} on {$guildName}");
            $msgData->user->reply($msg);
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
            "name" => "tq",
            "trigger" => array("!tq"),
            "information" => "Shows the current status of the Tranquility server",
            "timerFrequency" => 0
        );
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

    }
}