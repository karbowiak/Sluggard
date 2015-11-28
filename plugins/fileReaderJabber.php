<?php

class fileReaderJabber
{
    var $config;
    var $discord;
    var $db = "/tmp/discord.db";
    var $lastCheck = 0;

    function init($config, $discord)
    {
        $this->config = $config;
        $this->discord = $discord;
        if(!is_file($this->db))
            touch($this->db);
    }

    function information()
    {
        return array(
            "name" => "",
            "trigger" => array(),
            "information" => ""
        );
    }

    function tick()
    {
        echo filemtime($this->db)."\n";
        echo $this->lastCheck."\n";
        if(filemtime($this->db) >= $this->lastCheck)
        {
            $data = file($this->db);
            if($data)
            {
                $message = "";
                foreach($data as $row)
                {
                    $row = str_replace("\n", "", str_replace("\r", "", $row));
                    $channelID = 119136919346085888; // Pings channel on discord
                    if($row == "" || $row == " ")
                        continue;

                    $message .= $row . " | ";
                    usleep(300000);
                }

                // Remove |  from the line or whatever else is at the last two characters in the string
                $message = substr($message, 0, -2);
                $this->discord->api("channel")->messages()->create($channelID, "@ everyone | " . $message);
            }
            $h = fopen($this->db, "w+");
            fclose($h);
            chmod($this->db, 0777);
            $data = null;
            $h = null;
        }
        $this->lastCheck = time();
    }

    function onMessage($message)
    {
    }
}
