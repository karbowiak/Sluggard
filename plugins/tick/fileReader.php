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

                foreach($this->channelConfig as $chanName => $chanConfig) {
                    if($chanConfig["searchString"] == false) {
                        $message = $chanConfig["textStringPrepend"] . " " . $message . " " .$chanConfig["textStringAppend"];
                        $channelID = $chanConfig["channelID"];
                    }
                    elseif(stristr($message, $chanConfig["searchString"])) {
                        $message = $chanConfig["textStringPrepend"] . " " . $message . " " . $chanConfig["textStringAppend"];
                        $channelID = $chanConfig["channelID"];
                    }
                    else {
                        $channelID = $chanConfig["channelID"];
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
