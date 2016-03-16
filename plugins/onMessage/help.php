<?php

use Sluggard\SluggardApp;

/**
 * Class help
 */
class help
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
     * help constructor.
     * @param $discord
     * @param SluggardApp $app
     */
    public function __construct($discord, SluggardApp $app)
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
            global $plugins;
            $channelName = $msgData->channel->name;
            $guildName = $msgData->guild->name;
            $messageString = $data["messageString"];

            if (!$messageString) {
                // Show all modules available
                $commands = array();
                foreach ($plugins["onMessage"] as $plugin) {
                    $info = $plugin->information();
                    if (!empty($info["name"]))
                        $commands[] = $info["name"];
                }

                $msgData->user->reply("**Help:** No specific plugin requested, here is a list of plugins available: **" . implode("** | **", $commands) . "**");
            } else {
                foreach ($plugins["onMessage"] as $plugin) {
                    if ($messageString == $plugin->information()["name"]) {
                        $msgData->user->reply($plugin->information()["information"]);
                    }
                }
            }

            //$this->log->info("Sending time info to {$channelName} on {$guildName}");
            //$msgData->user->reply($msg);
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
            "name" => "help",
            "trigger" => array("!help"),
            "information" => "Shows help for other plugins",
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