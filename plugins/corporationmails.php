<?php

/**
 * Class corporationmails
 */
class corporationmails
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
    var $lastCheck;
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
    var $apiKeyID;
    /**
     * @var
     */
    var $vCode;
    /**
     * @var
     */
    var $characterID;
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
        $this->toIDs = array(98047305, 99005805); // 4M-Corp and The-Culture
        $this->toDiscordChannel = 120639051261804544; // Corpmails channel
        $this->newestMailID = getPermCache("newestCorpMailID");
        $this->maxID = 0;
        $this->apiKeyID = $config["eve"]["keyID"];
        $this->vCode = $config["eve"]["vCode"];
        $this->characterID = $config["eve"]["characterID"];
    }

    /**
     *
     */
    function tick()
    {
        if($this->lastCheck <= time())
        {
            $updateMaxID = false;
            $data = json_decode(json_encode(simplexml_load_string(downloadData("https://api.eveonline.com/char/MailMessages.xml.aspx?keyID={$this->apiKeyID}&vCode={$this->vCode}&characterID={$this->characterID}"), "SimpleXMLElement", LIBXML_NOCDATA)), true);
            $data = $data["result"]["rowset"]["row"];

            $mails = array();
            foreach($data as $getFuckedCCP)
                $mails[] = $getFuckedCCP["@attributes"];

            usort($mails, array($this, "sortByDate"));

            foreach($mails as $mail)
            {
                if(in_array($mail["toCorpOrAllianceID"], $this->toIDs) && $mail["messageID"] > $this->newestMailID) {
                    $sentBy = $mail["senderName"];
                    $title = $mail["title"];
                    $sentDate = $mail["sentDate"];
                    $content = strip_tags(str_replace("<br>", "\n", json_decode(json_encode(simplexml_load_string(downloadData("https://api.eveonline.com/char/MailBodies.xml.aspx?keyID={$this->apiKeyID}&vCode={$this->vCode}&characterID={$this->characterID}&ids=" . $mail["messageID"]), "SimpleXMLElement", LIBXML_NOCDATA)))->result->rowset->row));

                    // Stitch the mail together
                    $msg = "**Mail By: **{$sentBy}\n";
                    $msg .= "**Sent Date: **{$sentDate}\n";
                    $msg .= "**Title: ** {$title}\n";
                    $msg .= "**Content: **\n\n";
                    $msg .= $content;

                    // Send the mails to the channel
                    $this->discord->api("channel")->messages()->create($this->toDiscordChannel, $msg);

                    // Find the maxID so we don't spit this message out ever again
                    $this->maxID = max($mail["messageID"], $this->maxID);
                    $this->newestMailID = $mail["messageID"];
                    $updateMaxID = true;
                }
            }

            // set the maxID
            if($updateMaxID)
                setPermCache("newestCorpMailID", $this->maxID);

            // Only run once every 30 minutes
            $this->lastCheck = time() + 1800;
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

        /**
         * @param $msgData
         */
        function onMessageAdmin($msgData)
        {
        }

}
