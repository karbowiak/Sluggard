<?php

/**
 * Class fileReaderJabber
 */
class fileReader
{
    /**
     * @var
     */
    var $config;
    /**
     * @var
     */
    var $db;
    /**
     * @var
     */
    var $discord;
    /**
     * @var
     */
    var $channelConfig;
    /**
     * @var int
     */
    var $lastCheck = 0;
    /**
     * @var
     */
    var $logger;

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
        $this->channelConfig = $config["plugins"]["fileReader"]["channelConfig"];
        $this->db = $config["plugins"]["fileReader"]["db"];
        if (!is_file($this->db))
            touch($this->db);
    }

    /**
     * @return array
     */
    function information()
    {
        return array(
            "name" => "",
            "trigger" => array(),
            "information" => ""
        );
    }

    /**
     *
     */
    function tick()
    {
        if (filemtime($this->db) >= $this->lastCheck) {
            $data = file($this->db);
            if ($data) {
                $message = "";
                foreach ($data as $row) {
                    $row = str_replace("\n", "", str_replace("\r", "", str_replace("^@", "", $row)));
                    if ($row == "" || $row == " ")
                        continue;

                    $message .= $row . " | ";
                    usleep(300000);
                }

                // Remove |  from the line or whatever else is at the last two characters in the string
                $message = trim(substr($message, 0, -2));

                $defaultID = 0;
                foreach($this->channelConfig as $chanName => $chanConfig) {
                    // If a channel is marked as default (usually the first on the list) we populate defaultID here, just to make sure..
                    if($chanConfig["default"] == true)
                        $defaultID = $chanConfig["channelID"];

                    // Search for a channel where the search string matches the actual message
                    if(stristr($message, $chanConfig["searchString"])) {
                        $message = $chanConfig["textStringPrepend"] . " " . $message . " " . $chanConfig["textStringAppend"];
                        $channelID = $chanConfig["channelID"];
                    }
                    elseif($chanConfig["searchString"] == false) { // If no match was found, and searchString is false, just use that
                        $message = $chanConfig["textStringPrepend"] . " " . $message . " " .$chanConfig["textStringAppend"];
                        $channelID = $chanConfig["channelID"];
                    }
                    else { // If something fucked up, we'll just go this route..
                        $channelID = isset($defaultID) ? $defaultID : $chanConfig["channelID"]; // If default ID isn't set, then we just pick whatever we can..
                    }
                }

                $this->discord->api("channel")->messages()->create($channelID, $message);
            }
            $h = fopen($this->db, "w+");
            fclose($h);
            chmod($this->db, 0777);
            $data = null;
            $h = null;
        }
        clearstatcache();
        $this->lastCheck = time();
    }

    /**
     * @param $msgData
     */
    function onMessage($msgData)
    {
    }
}
