<?php

class user
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
            $user = stristr($data["messageString"], "@") ? str_replace("<@", "", str_replace(">", "", $data["messageString"])) : $data["messageString"];

            // Get data for user
            $userData = dbQueryRow("SELECT * FROM discordUsersSeen WHERE (name = :name OR id = :name)", array(":name" => $user));

            if ($userData) {
                $message = "```ID: {$userData["id"]}\nName: {$userData["name"]}\nisAdmin: {$userData["isAdmin"]}\nLast Seen: {$userData["lastSeen"]}\nLast Spoken: {$userData["lastSpoke"]}\nLast Status: {$userData["lastStatus"]}```";
                $this->logger->info("Sending user info to {$channelName} on {$guildName}");
                $this->discord->api("channel")->messages()->create($channelID, $message);
            } else
                $this->discord->api("channel")->messages()->create($channelID, "**Error:** no such user in the users table.");
        }
    }

    function information()
    {
        return array(
            "name" => "user",
            "trigger" => array("!user"),
            "information" => "Shows Discord information on a user. Example: **!user Karbowiak**"
        );
    }

    /**
     * @param $msgData
     */
    function onMessageAdmin($msgData)
    {
    }

}