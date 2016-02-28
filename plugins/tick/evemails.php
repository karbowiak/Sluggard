<?php

/**
 * Class corporationmails
 */
class evemails
{
    /**
     * @var
     */
    var $config;
    /**
     * @var
     */
    var $discord;
    /**
     * @var
     */
    var $logger;
    /**
     * @var
     */
    var $nextCheck;
    /**
     * @var
     */
    var $toIDs;
    /**
     * @var
     */
    var $toDiscordChannel;

    /**
     * @var
     */
    var $newestMailID;
    /**
     * @var
     */
    var $maxID;
    /**
     * @var
     */
    var $keyCount;
    /**
     * @var
     */
    var $keys;

    /**
     * @param $config
     * @param $discord
     * @param $logger
     */
    function init($config, $discord, $logger)
    {
        $this->config = $config;
        $this->discord = $discord;
        $this->logger = $logger;
        $this->toIDs = $config["plugins"]["evemails"]["fromIDs"];
        $this->toDiscordChannel = $config["plugins"]["evemails"]["channelID"];
        $this->newestMailID = getPermCache("newestCorpMailID");
        $this->maxID = 0;
        $this->keyCount = count($config["eve"]["apiKeys"]);
        $this->keys = $config["eve"]["apiKeys"];
        $this->nextCheck = 0;

        // Schedule all the apiKeys for the future
        $keyCounter = 0;
        foreach($this->keys as $keyOwner => $apiData) {
            $keyID = $apiData["keyID"];
            $characterID = $apiData["characterID"];

            if($keyCounter == 0) // Schedule it for right now
                setPermCache("corpMailCheck{$keyID}{$keyOwner}{$characterID}", time() - 5);
            else {
                $rescheduleTime = time() + ((1805 / $this->keyCount) * $keyCounter);
                setPermCache("corpMailCheck{$keyID}{$keyOwner}{$characterID}", $rescheduleTime);
            }
            $keyCounter++;
        }
    }

    /**
     *
     */
    function tick()
    {
        $check = true;
        foreach ($this->keys as $keyOwner => $api) {
            if ($check == false)
                continue;

            $keyID = $api["keyID"];
            $vCode = $api["vCode"];
            $characterID = $api["characterID"];
            $lastChecked = getPermCache("corpMailCheck{$keyID}{$keyOwner}{$characterID}");

            if ($lastChecked <= time()) {
                $this->logger->info("Checking API Key {$keyID} belonging to {$keyOwner} for new corp mails");
                $this->checkMails($keyID, $vCode, $characterID);
                setPermCache("corpMailCheck{$keyID}{$keyOwner}{$characterID}", time() + 1807); // Reschedule it's check for 30minutes from now (plus 7 seconds, because CCP isn't known to adhere strictly to timeouts, lol)
                $check = false;
            }
        }
    }

    function checkMails($keyID, $vCode, $characterID)
    {
        $updateMaxID = false;
        $url = "https://api.eveonline.com/char/MailMessages.xml.aspx?keyID={$keyID}&vCode={$vCode}&characterID={$characterID}";
        $data = json_decode(json_encode(simplexml_load_string(downloadData($url), "SimpleXMLElement", LIBXML_NOCDATA)), true);
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
                $content = strip_tags(str_replace("<br>", "\n", json_decode(json_encode(simplexml_load_string(downloadData($url), "SimpleXMLElement", LIBXML_NOCDATA)))->result->rowset->row));

                // Stitch the mail together
                $msg = "**Mail By: **{$sentBy}\n";
                $msg .= "**Sent Date: **{$sentDate}\n";
                $msg .= "**Title: ** {$title}\n";
                $msg .= "**Content: **\n";
                $msg .= htmlspecialchars_decode(trim($content));

                // Send the mails to the channel
                $this->discord->api("channel")->messages()->create($this->toDiscordChannel, $msg);
                sleep(1); // Lets sleep for a second, so we don't rage spam

                // Find the maxID so we don't spit this message out ever again
                $this->maxID = max($mail["messageID"], $this->maxID);
                $this->newestMailID = $this->maxID; //$mail["messageID"];
                $updateMaxID = true;

                // set the maxID
                if ($updateMaxID)
                    setPermCache("newestCorpMailID", $this->maxID);
            }
        }
    }

    /**
     * @param $a
     * @param $b
     * @return int
     */
    function sortByDate($a, $b)
    {
        return strcmp($a["sentDate"], $b["sentDate"]);
    }

    /**
     * @param $msgData
     */
    function onMessage($msgData)
    {
    }

    /**
     * @return array
     */
    function information()
    {
        return array(
            "name" => "",
            "trigger" => array(""),
            "information" => ""
        );
    }
}
