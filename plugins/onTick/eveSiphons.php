<?php

use Sluggard\SluggardApp;

/**
 * Class eveSiphons
 */
class eveSiphons
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
     * @var bool
     */
    private $run = false;
    /**
     * @param $discord
     * @param SluggardApp $app
     */
    public function __construct(\Discord\Discord &$discord, SluggardApp &$app)
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

        $this->toDiscordChannel = $this->config->get("channelID", "siphons");
        $this->keyCount = count($this->config->get("apiKeys", "eve"));
        $this->keys = $this->config->get("apiKeys", "eve");
        $this->nextCheck = 0;

        // Schedule all the apiKeys for the future
        $keyCounter = 0;
        foreach ($this->keys as $keyOwner => $apiData) {
            $keyID = $apiData["keyID"];
            if ($apiData["corpKey"] == false)
                continue;

            $this->run = true;
            if ($keyCounter == 0) // Schedule it for right now
                $this->storage->set("siphonCheck{$keyID}{$keyOwner}", time() - 5);
            else {
                $rescheduleTime = time() + ((21602 / $this->keyCount) * $keyCounter); // We can only check keys every 6 hours..
                $this->storage->set("siphonCheck{$keyID}{$keyOwner}", $rescheduleTime);
            }
            $keyCounter++;
        }
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
        // If there are no corp api keys, don't run..
        if($this->run == false)
            return;

        $check = true;
        foreach ($this->keys as $keyOwner => $api) {
            try {
                if ($check == false)
                    continue;

                $keyID = $api["keyID"];
                $vCode = $api["vCode"];
                if ($api["corpKey"] == false)
                    continue;

                $lastChecked = $this->storage->get("siphonCheck{$keyID}{$keyOwner}");

                if ($lastChecked <= time()) {
                    $this->log->info("Checking API Key {$keyID} belonging to {$keyOwner} for Siphons");
                    $this->checkForSiphons($keyID, $vCode);
                    $this->storage->set("siphonCheck{$keyID}{$keyOwner}", time() + 21602); // Reschedule it's check for new data in 6 hours (plus 2 seconds)
                    $check = false;
                }
            } catch (\Exception $e) {
                $this->log->err("Error with eve siphon checker: " . $e->getMessage());
            }
        }
    }

    /**
     * @param $keyID
     * @param $vCode
     */
    private function checkForSiphons($keyID, $vCode)
    {
        try { // Seriously CCP.. *sigh*
            $url = "https://api.eveonline.com/corp/AssetList.xml.aspx?keyID={$keyID}&vCode={$vCode}";
            $data = json_decode(json_encode(simplexml_load_string($this->curl->getData($url), "SimpleXMLElement", LIBXML_NOCDATA)), true);
            $data = $data["result"]["rowset"]["row"];

            // If there is no data, just quit..
            if (empty($data))
                return;

            $fixedData = array();


            foreach ($data as $key => $getFuckedCCP) {
                if ($getFuckedCCP["@attributes"]["typeID"] == 14343) {
                    $fixedData[$key] = $getFuckedCCP["@attributes"];
                    $fixedData[$key]["siloContents"] = @$getFuckedCCP["rowset"]["row"]["@attributes"];
                }
            }


            foreach ($fixedData as $silos) {
                $locationID = $silos["locationID"];
                $locationName = $this->ccpDB->queryField("SELECT solarSystemName FROM mapSolarSystems WHERE solarSystemID = :id", "solarSystemName", array(":id" => $locationID));
                $siloContents = $silos["siloContents"];
                $quantity = $siloContents["quantity"];
                $siloHarvesting = $siloContents["typeID"];
                $siloHarvestingName = $this->ccpDB->queryField("SELECT typeName FROM invTypes WHERE typeID = :id", "typeName", array(":id" => $siloHarvesting));

                // Skip this silo if it has no data
                if ($siloContents == null || empty($siloContents))
                    continue;

                // If we're being siphoned
                if ($quantity % 100 != 0) {
                    $msg = "**Alert:** Possible Siphon detected in {$locationName}.. What is being siphoned: {$siloHarvestingName}";
                    $channel = \Discord\Parts\Channel\Channel::find($this->toDiscordChannel);
                    $channel->sendMessage($msg);
                }
            }

        } catch (Exception $e) {
            $this->log->debug("Error: " . $e->getMessage());
        }
    }

    /**
     * When the bot's tick hits a specified time, this is started
     *
     * Runtime is defined in $this->information(), timerFrequency
     */
    public function onTimer()
    {

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
            "name" => "",
            "trigger" => array(""),
            "information" => "",
            "timerFrequency" => 21600
        );
    }
}