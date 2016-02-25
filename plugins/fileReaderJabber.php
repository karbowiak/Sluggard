<?php

/**
 * Class fileReaderJabber
 */
class fileReaderJabber
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
     * @var string
     */
    var $db = "/tmp/discord.db";
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
        $pings = 119136919346085888; // Pings channel on discord
        $intel = 149918425018400768; // Intel channel on discord
        $blackops = 149925578135306240; // Blackops channel on discord

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

	        if(stristr("blops")) {
			$channelID = $blackops;
			$message = "@everyone | " . $message;
		}
		elseif(stristr("intel")) {
			$channelID = $intel;
		}
		else {
			$channelID = $pings;
			$message = "@everyone | " . $message;
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

    /**
     * @param $msgData
     */
    function onMessageAdmin($msgData)
    {
    }
}
