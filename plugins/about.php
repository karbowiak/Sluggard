<?php

class about
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
            $gitRevision = gitRevision();
            $msg = "Hello, i'm EVEBot - i am a bot created for EVE Online related Discord servers. I am utterly useless for almost, anything else ;)\n";
            $msg .= "Also, i am the half-brother of Sovereign, atleast in the Blasto 7 movie..\n\n";
            $msg .= "**About Me:**\n";
            $msg .= "Author: Karbowiak (Discord ID: 118440839776174081)\n";
            $msg .= "Library: discord-hypertext (PHP)\n";
            $msg .= "Current version: ``" . $gitRevision["short"]. "`` (Last Update: ``" . $gitRevision["lastChangeDate"] . "``)\n";
            $msg .= "Github Repo: ``https://github.com/karbowiak/Sluggard``";

            $this->logger->info("Sending about info to {$channelName} on {$guildName}");
            $this->discord->api("channel")->messages()->create($channelID, $msg);
        }
    }

    /**
     * @return array
     */
    function information()
    {
        return array(
            "name" => "about",
            "trigger" => array("!about"),
            "information" => "Shows information on the bot, who created it, what library it's using, revision, and other stats. Example: !about"
        );
    }

        /**
         * @param $msgData
         */
        function onMessageAdmin($msgData)
        {
        }

}
