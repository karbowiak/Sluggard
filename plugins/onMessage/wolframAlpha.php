<?php

use Sluggard\SluggardApp;

/**
 * Class wolframAlpha
 */
class wolframAlpha {
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
    private $wolframAlpha;

    /**
     * wolframAlpha constructor.
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
        require_once(BASEDIR . "/src/wolframAlpha/WolframAlphaEngine.php");
        $appID = $this->config->get("appID", "wolframalpha");
        $this->wolframAlpha = $appID != null ? new WolframAlphaEngine($appID) : null;
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
            $messageString = $data["messageString"];

            $response = $this->wolframAlpha->getResults($messageString);

            // There was an error
            if($response->isError())
                var_dump($response->error);

            $guess = $response->getPods();
            if(isset($guess[1])) {
                $guess = $guess[1]->getSubpods();
                $text = $guess[0]->plaintext;
                $image = $guess[0]->image->attributes["src"];

                if(stristr($text, "\n"))
                    $text = str_replace("\n", " | ", $text);

                $msg = "{$text}\n$image";
                $msgData->user->reply($msg);
            }
            //$this->log->info("Sending time info to {$channelName} on {$guildName}");
            //$msgData->user->reply($msg);
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
            "name" => "wolf",
            "trigger" => array("!wolf"),
            "information" => "Asks wolframAlpha a question, and returns with an answer. If there is one",
            "timerFrequency" => 0
        );
    }
}