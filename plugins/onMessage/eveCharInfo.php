<?php

use Sluggard\SluggardApp;

/**
 * Class eveCharInfo
 */
class eveCharInfo {
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

    /**
     * eveCharInfo constructor.
     * @param $discord
     * @param SluggardApp $app
     */
    public function __construct($discord, SluggardApp $app) {
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

            // Most EVE players on Discord use their ingame name, so lets support @highlights
            $messageString = stristr($data["messageString"], "@") ? str_replace("<@", "", str_replace(">", "", $data["messageString"])) : $data["messageString"];
            if(is_numeric($messageString)) // The person used @highlighting, so now we got a discord id, lets map that to a name
                $messageString = $this->sluggardDB->queryField("SELECT name FROM usersSeen WHERE id = :id", "name", array(":id" => $messageString));

            $url = "http://rena.karbowiak.dk/api/search/character/{$messageString}/";
            $data = @json_decode($this->curl->getData($url), true)["character"];

            if(empty($msgData))
                return $msgData->user->reply("**Error:** no results was returned.");

            if(count($msgData) > 1) {
                $results = array();
                foreach($msgData as $char)
                    $results[] = $char["characterName"];

                return $msgData->user->reply("**Error:** more than one result was returned: " . implode(", ", $results));
            }

            // Get stats
            $characterID = $data[0]["characterID"];
            $statsURL = "https://beta.eve-kill.net/api/charInfo/characterID/" . urlencode($characterID) ."/";
            $stats = json_decode($this->curl->getData($statsURL), true);

            if(empty($stats))
                return $msgData->user->reply("**Error:** no data available");

            $characterName = @$stats["characterName"];
            $corporationName = @$stats["corporationName"];
            $allianceName = isset($stats["allianceName"]) ? $stats["allianceName"] : "None";
            $factionName = isset($stats["factionName"]) ? $stats["factionName"] : "None";
            $securityStatus = @$stats["securityStatus"];
            $lastSeenSystem = @$stats["lastSeenSystem"];
            $lastSeenRegion = @$stats["lastSeenRegion"];
            $lastSeenShip = @$stats["lastSeenShip"];
            $lastSeenDate = @$stats["lastSeenDate"];
            $corporationActiveArea = @$stats["corporationActiveArea"];
            $allianceActiveArea = @$stats["allianceActiveArea"];
            $soloKills = @$stats["soloKills"];
            $blobKills = @$stats["blobKills"];
            $lifeTimeKills = @$stats["lifeTimeKills"];
            $lifeTimeLosses = @$stats["lifeTimeLosses"];
            $amountOfSoloPVPer = @$stats["percentageSoloPVPer"];
            $ePeenSize = @$stats["ePeenSize"];
            $facepalms = @$stats["facepalms"];
            $lastUpdated = @$stats["lastUpdatedOnBackend"];
            $url = "https://beta.eve-kill.net/character/" . $stats["characterID"] . "/";


            $msg = "```characterName: {$characterName}
corporationName: {$corporationName}
allianceName: {$allianceName}
factionName: {$factionName}
securityStatus: {$securityStatus}
lastSeenSystem: {$lastSeenSystem}
lastSeenRegion: {$lastSeenRegion}
lastSeenShip: {$lastSeenShip}
lastSeenDate: {$lastSeenDate}
corporationActiveArea: {$corporationActiveArea}
allianceActiveArea: {$allianceActiveArea}
soloKills: {$soloKills}
blobKills: {$blobKills}
lifeTimeKills: {$lifeTimeKills}
lifeTimeLosses: {$lifeTimeLosses}
percentageSoloPVPer: {$amountOfSoloPVPer}
ePeenSize: {$ePeenSize}
facepalms: {$facepalms}
lastUpdated: $lastUpdated```
For more info, visit: $url";

            $this->log->info("Sending char info to {$channelName} on {$guildName}");
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
            "name" => "char",
            "trigger" => array("!char"),
            "information" => "",
            "timerFrequency" => 0
        );
    }
}