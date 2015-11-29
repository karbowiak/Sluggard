<?php

class userInfo
{
    var $config;
    var $discord;
    var $logger;

    function init($config, $discord, $logger)
    {
        $this->config = $config;
        $this->discord = $discord;
        $this->logger = $logger;
    }

    function information()
    {
        return array(
            "name" => "userInfo",
            "trigger" => array("!user"),
            "information" => "Shows information on a user"
        );
    }

    function tick()
    {
    }

    function onMessage($msgData)
    {
        $message = $msgData["message"]["message"];
        $channelName = $msgData["channel"]["name"];
        $guildName = $msgData["guild"]["name"];
        $channelID = $msgData["message"]["channelID"];

        $data = command($message, $this->information()["trigger"]);
        if (isset($data["trigger"])) {
            // Get data for user
            $userData = dbQueryRow("SELECT * FROM users WHERE name = :name", array(":name" => $data["messageString"]));


            if($userData) {
                $message = "```ID: {$userData["id"]}\nName: {$userData["name"]}\nLast Seen: {$userData["lastSeen"]}\nLast Spoken: {$userData["lastSpoke"]}\nLast Status: {$userData["lastStatus"]}```";
                $this->logger->info("Sending userInfo info to {$channelName} on {$guildName}");
                $this->discord->api("channel")->messages()->create($channelID, $message);
            }
            else
                $this->discord->api("channel")->messages()->create($channelID, "**Error:** no such user in the users table.");
        }
    }
}