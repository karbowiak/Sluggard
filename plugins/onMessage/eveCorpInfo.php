<?php

use Sluggard\SluggardApp;

/**
 * Class eveCorpInfo
 */
class eveCorpInfo {
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
     * eveCorpInfo constructor.
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
    }

    /**
     * When a message arrives that contains a trigger, this is started
     *
     * @param $msgData
     */
    public function onMessage($msgData) {
        $message = $msgData->message->message;
        $data = $this->trigger->trigger($message, $this->information()["trigger"]);

        if(isset($data["trigger"])) {
            $channelName = $msgData->channel->name;
            $guildName = $msgData->guild->name;
            $messageString = $data["messageString"];

            $url = "http://rena.karbowiak.dk/api/search/corporation/{$messageString}/";
            $data = @json_decode($this->curl->getData($url), true)["corporation"];

            if(empty($data))
                return $msgData->user->reply("**Error:** no results was returned.");

            if(count($data) > 1) {
                $results = array();
                foreach($data as $corp)
                    $results[] = $corp["corporationName"];

                return $msgData->user->reply("**Error:** more than one result was returned: " . implode(", ", $results));
            }

            // Get stats
            $corporationID = $data[0]["corporationID"];
            $statsURL = "https://beta.eve-kill.net/api/corpInfo/corporationID/" . urlencode($corporationID) ."/";
            $stats = json_decode($this->curl->getData($statsURL), true);

            if(empty($stats))
                return $msgData->user->reply("**Error:** no data available");

            $corporationName = @$stats["corporationName"];
            $allianceName = isset($stats["allianceName"]) ? $stats["allianceName"] : "None";
            $factionName = isset($stats["factionName"]) ? $stats["factionName"] : "None";
            $ceoName = @$stats["ceoName"];
            $homeStation = @$stats["stationName"];
            $taxRate = @$stats["taxRate"];
            $corporationActiveArea = @$stats["corporationActiveArea"];
            $allianceActiveArea = @$stats["allianceActiveArea"];
            $lifeTimeKills = @$stats["lifeTimeKills"];
            $lifeTimeLosses = @$stats["lifeTimeLosses"];
            $memberCount = @$stats["memberArrayCount"];
            $superCaps = @count($stats["superCaps"]);
            $ePeenSize = @$stats["ePeenSize"];
            $url = "https://beta.eve-kill.net/corporation/" . @$stats["corporationID"] . "/";


            $msg = "```corporationName: {$corporationName}
allianceName: {$allianceName}
factionName: {$factionName}
ceoName: {$ceoName}
homeStation: {$homeStation}
taxRate: {$taxRate}
corporationActiveArea: {$corporationActiveArea}
allianceActiveArea: {$allianceActiveArea}
lifeTimeKills: {$lifeTimeKills}
lifeTimeLosses: {$lifeTimeLosses}
memberCount: {$memberCount}
superCaps: {$superCaps}
ePeenSize: {$ePeenSize}
```
For more info, visit: $url";

            $this->log->info("Sending corp info to {$channelName} on {$guildName}");
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
            "name" => "corp",
            "trigger" => array("!corp"),
            "information" => "Shows corporation information, fetched from projectRena",
            "timerFrequency" => 0
        );
    }
}