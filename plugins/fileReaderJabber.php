<?php

class fileReaderJabber
{
    var $config;
    var $discord;
    var $db = "/tmp/discord.db";

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
        $data = file($this->db);
        if(!empty($data))
        {
            $message = "";
            foreach($data as $line)
            {
                $line = str_replace("\n", "", str_replace("\r", "", $line));
                $channelID = 119136919346085888; // 4M Ping channel on discord, needs to be un-hardcoded at some point
                // If the line doesn't contain anything, skip it.. happens when jabber is involved.. god i hate jabber
                if($line == "" || $line == " ")
                    continue;

                $message .= $line . " | ";
            }

            // Remove |  from the line or whatever else is at the last two characters in the string
            $message = substr($message, 0, -2);
            $this->discord->api("channel")->messages()->create($channelID, "@ everyone | " . $message);

            // We're done with the file, lets truncate it (Crude but effective)
            file_put_contents($this->db, "");
        }
    }

    function onMessage($message)
    {
    }
}
