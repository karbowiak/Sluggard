<?php

use Sluggard\SluggardApp;

/**
 * Class evePrice
 */
class evePrice {
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
    private $solarSystems;
    /**
     * @var array
     */
    private $triggers = array();

    /**
     * evePrice constructor.
     * @param $discord
     * @param SluggardApp $app
     */
    public function __construct($discord, SluggardApp $app) {
        $this->app = $app;
        $this->config = $app->config;
        $this->discord = $discord;
        $this->log = $app->log;
        $this->sluggardDB = $app->sluggarddata;
        $this->ccpDB = $app->ccpdata;
        $this->curl = $app->curl;
        $this->storage = $app->storage;
        $this->trigger = $app->triggercommand;

        $systems = $this->ccpDB->query("SELECT solarSystemName, solarSystemID FROM mapSolarSystems", array());
        foreach ($systems as $system) {
            $this->solarSystems[strtolower($system["solarSystemName"])] = $system["solarSystemID"];
            $this->triggers[] = "!" . strtolower($system["solarSystemName"]);
        }
        $this->triggers[] = "!pc";
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
            $systemName = $data["trigger"];
            $itemName = $data["messageString"];

            $single = $this->ccpDB->queryRow("SELECT typeID, typeName FROM invTypes WHERE typeName = :item COLLATE NOCASE", array(":item" => ucfirst($itemName)));
            $multiple = $this->ccpDB->query("SELECT typeID, typeName FROM invTypes WHERE typeName LIKE :item COLLATE NOCASE LIMIT 5", array(":item" => "%" . ucfirst($itemName) . "%"));

            // Quick lookups
            if(isset($quickLookUps[$itemName]))
                $single = $quickLookUps[$itemName];

            // Sometimes the multiple lookup is returning just one
            if(count($multiple) == 1)
                $single = $multiple[0];

            // If there are multiple results, and not a single result, it's an error
            if(empty($single) && !empty($multiple)) {
                $items = array();
                foreach($multiple as $item)
                    $items[] = $item["typeName"];

                $items = implode(", ", $items);
                return $msgData->user->reply("**Multiple results found:** {$items}");
            }

            // If there is a single result, we'll get data now!
            if($single) {
                $typeID = $single["typeID"];
                $typeName = $single["typeName"];

                $solarSystemID = $systemName == "pc" ? "global" : $this->solarSystems[$systemName];

                // Get pricing data
                if($solarSystemID == "global")
                    $data = new SimpleXMLElement($this->curl->getData("https://api.eve-central.com/api/marketstat?typeid={$typeID}"));
                else
                    $data = new SimpleXMLElement($this->curl->getData("https://api.eve-central.com/api/marketstat?usesystem={$solarSystemID}&typeid={$typeID}"));

                $lowBuy = number_format((float) $data->marketstat->type->buy->min, 2);
                $avgBuy = number_format((float) $data->marketstat->type->buy->avg, 2);
                $highBuy = number_format((float) $data->marketstat->type->buy->max, 2);
                $lowSell = number_format((float) $data->marketstat->type->sell->min, 2);
                $avgSell = number_format((float) $data->marketstat->type->sell->avg, 2);
                $highSell = number_format((float) $data->marketstat->type->sell->max, 2);

                $this->log->info("Sending pricing info to {$channelName} on {$guildName}");
                $solarSystemName = $systemName == "pc" ? "Global" : ucfirst($systemName);
                $messageData = "```
typeName: {$typeName}
solarSystemName: {$solarSystemName}
Buy:
  Low: {$lowBuy}
  Avg: {$avgBuy}
  High: {$highBuy}
Sell:
  Low: {$lowSell}
  Avg: {$avgSell}
  High: {$highSell}```";
                $msgData->user->reply($messageData);
            }
            else {
                $msgData->user->reply("**Error:** ***{$itemName}*** not found");
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
            "name" => "pc",
            "trigger" => $this->triggers,
            "information" => "Returns pricing information from all over EVE.",
            "timerFrequency" => 0
        );
    }
}