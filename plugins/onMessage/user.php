<?php

use Sluggard\SluggardApp;

/**
 * Class user
 */
class user {
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
     * user constructor.
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
    public function onMessage(stdClass $msgData) {
        $message = $msgData->message->message;
        $data = $this->trigger->trigger($message, $this->information()["trigger"]);

        if(isset($data["trigger"])) {
            $channelName = $msgData->channel->name;
            $guildName = $msgData->guild->name;

            $user = stristr($data["messageString"], "@") ? str_replace("<@", "", str_replace(">", "", $data["messageString"])) : $data["messageString"];

            // Get data for user
            $userData = $this->sluggardDB->queryRow("SELECT * FROM usersSeen WHERE (name = :name COLLATE NOCASE OR id = :name)", array(":name" => $user));

            if($userData) {
                $msg = "```ID: {$userData["id"]}\nName: {$userData["name"]}\nisAdmin: {$userData["isAdmin"]}\nLast Seen: {$userData["lastSeen"]}\nLast Spoken: {$userData["lastSpoke"]}\nLast Status: {$userData["lastStatus"]}```";
                $this->log->info("Sending time info to {$channelName} on {$guildName}");
                $msgData->user->reply($msg);
            } else {
                $msgData->user->reply("**Error:** no such user in the users table");
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
            "name" => "user",
            "trigger" => array("!user"),
            "information" => "Shows Discord information, on a particular user",
            "timerFrequency" => 0
        );
    }
}