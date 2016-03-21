<?php

use Sluggard\SluggardApp;

/**
 * Class eveMails
 */
class eveMails {
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
     * @private
     */
    private $nextCheck;
    /**
     * @private
     */
    private $toIDs;
    /**
     * @private
     */
    private $toDiscordChannel;

    /**
     * @private
     */
    private $newestMailID;
    /**
     * @private
     */
    private $maxID;
    /**
     * @private
     */
    private $keyCount;
    /**
     * @private
     */
    private $keys;

    /**
     * eveMails constructor.
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

        $this->toIDs = $this->config->get("fromIDs", "evemails");
        $this->toDiscordChannel = $this->config->get("channelID", "evemails");
        $this->newestMailID = $this->storage->get("newestCorpMailID");
        $this->maxID = 0;
        $this->keyCount = count($this->config->get("apiKeys", "eve"));
        $this->keys = $this->config->get("apiKeys", "eve");
        $this->nextCheck = 0;

        // Schedule all the apiKeys for the future
        $keyCounter = 0;
        foreach($this->keys as $keyOwner => $apiData) {
            $keyID = $apiData["keyID"];
            $characterID = $apiData["characterID"];

            if($keyCounter == 0) // Schedule it for right now
                $this->storage->set("corpMailCheck{$keyID}{$keyOwner}{$characterID}", time() - 5);
            else {
                $rescheduleTime = time() + ((1805 / $this->keyCount) * $keyCounter);
                $this->storage->set("corpMailCheck{$keyID}{$keyOwner}{$characterID}", $rescheduleTime);
            }
            $keyCounter++;
        }
    }

    /**
     * When a message arrives that contains a trigger, this is started
     *
     * @param $msgData
     */
    public function onMessage($msgData) {

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
        $check = true;
        foreach ($this->keys as $keyOwner => $api) {
            try {
                if ($check == false)
                    return;

                $keyID = $api["keyID"];
                $vCode = $api["vCode"];
                $characterID = $api["characterID"];
                $lastChecked = $this->storage->get("corpMailCheck{$keyID}{$keyOwner}{$characterID}");

                if ($lastChecked <= time()) {
                    $this->log->info("Checking API Key {$keyID} belonging to {$keyOwner} for new corp mails");
                    $this->checkMails($keyID, $vCode, $characterID);
                    $this->storage->set("corpMailCheck{$keyID}{$keyOwner}{$characterID}", time() + 1807); // Reschedule it's check for 30minutes from now (plus 7 seconds, because CCP isn't known to adhere strictly to timeouts, lol)
                    $check = false;
                }
            } catch (\Exception $e) {
                $this->log->err("Error with eve mail checker: " . $e->getMessage());
            }
        }
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
            "name" => "eveMails",
            "trigger" => array(""),
            "information" => "",
            "timerFrequency" => 0
        );
    }

    /**
     * @param $a
     * @param $b
     * @return int
     */
    private function sortByDate($a, $b)
    {
        return strcmp($a["sentDate"], $b["sentDate"]);
    }

    private function checkMails($keyID, $vCode, $characterID)
    {
        $updateMaxID = false;
        $url = "https://api.eveonline.com/char/MailMessages.xml.aspx?keyID={$keyID}&vCode={$vCode}&characterID={$characterID}";
        $data = json_decode(json_encode(simplexml_load_string($this->curl->getData($url), "SimpleXMLElement", LIBXML_NOCDATA)), true);
        $data = $data["result"]["rowset"]["row"];

        $mails = array();

        // Sometimes there is only ONE notification, so.. yeah..
        if (count($data) > 1) {
            foreach ($data as $getFuckedCCP)
                $mails[] = $getFuckedCCP["@attributes"];
        } else
            $mails[] = $data["@attributes"];

        usort($mails, array($this, "sortByDate"));

        foreach ($mails as $mail) {
            if (in_array($mail["toCorpOrAllianceID"], $this->toIDs) && $mail["messageID"] > $this->newestMailID) {
                $sentBy = $mail["senderName"];
                $title = $mail["title"];
                $sentDate = $mail["sentDate"];
                $url = "https://api.eveonline.com/char/MailBodies.xml.aspx?keyID={$keyID}&vCode={$vCode}&characterID={$characterID}&ids=" . $mail["messageID"];
                $content = strip_tags(str_replace("<br>", "\n", json_decode(json_encode(simplexml_load_string($this->curl->getData($url), "SimpleXMLElement", LIBXML_NOCDATA)))->result->rowset->row));
                $messageSplit = str_split($content, 1850);

                // Stitch the mail together
                $msg = "**Mail By: **{$sentBy}\n";
                $msg .= "**Sent Date: **{$sentDate}\n";
                $msg .= "**Title: ** {$title}\n";
                $msg .= "**Content: **\n";
                $msg .= htmlspecialchars_decode(trim($messageSplit[0]));

                // Send the mails to the channel
                $channel = \Discord\Parts\Channel\Channel::find($this->toDiscordChannel);
                $channel->sendMessage($msg);
                sleep(1); // Lets sleep for a second, so we don't rage spam
                $channel->sendMessage($messageSplit[1]);

                // Find the maxID so we don't spit this message out ever again
                $this->maxID = max($mail["messageID"], $this->maxID);
                $this->newestMailID = $this->maxID; //$mail["messageID"];
                $updateMaxID = true;

                // set the maxID
                if ($updateMaxID)
                    $this->storage->set("newestCorpMailID", $this->maxID);
            }
        }
    }
}
