<?php

/**
 * Class user
 */
class user
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
     * @param $config
     * @param $discord
     * @param $logger
     */
    function init($config, $discord, $logger)
    {
        $this->config = $config;
        $this->discord = $discord;
        $this->logger = $logger;
    }

    /**
     *
     */
    function tick()
    {
    }

    /**
     * @param $msgData
     */
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
            $userData = dbQueryRow("SELECT * FROM usersSeen WHERE (name = :name COLLATE NOCASE OR id = :name)", array(":name" => $user));

            if ($userData) {
                $message = "```ID: {$userData["id"]}\nName: {$userData["name"]}\nisAdmin: {$userData["isAdmin"]}\nLast Seen: {$userData["lastSeen"]}\nLast Spoken: {$userData["lastSpoke"]}\nLast Status: {$userData["lastStatus"]}```";
                $this->logger->info("Sending user info to {$channelName} on {$guildName}");
                $this->discord->api("channel")->messages()->create($channelID, $message);
            } else
                $this->discord->api("channel")->messages()->create($channelID, "**User Error:** no such user in the users table.");
        }
    }

    /**
     * @return array
     */
    function information()
    {
        return array(
            "name" => "user",
            "trigger" => array("!user"),
            "information" => "Shows Discord information on a user. Example: **!user Karbowiak**"
        );
    }
}