<?php

class corpApplication
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
    var $step;

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

        $this->step[] = 0; // array("discordID" => step);
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
        $fromID = $msgData["message"]["fromID"];
        $channelName = $msgData["channel"]["name"];
        $guildName = $msgData["guild"]["name"];
        $channelID = $msgData["message"]["channelID"];
        $isPrivate = $msgData["channel"]["is_private"];

        $data = command($message, $this->information()["trigger"]);
        if (isset($data["trigger"])) {
            $trigger = $data["trigger"];
            $messageArray = $data["messageArray"];
            $messageString = $data["messageString"];

            // Once a person tells the bot to pm a person with an application, this is where that happens, cause it's not a private conversation :D

            //$this->logger->info("Sending <> info to {$channelName} on {$guildName}");
            //$this->discord->api("channel")->messages()->create($channelID, $msg);
        }

        if (isset($data["trigger"]) && $isPrivate) {
            $trigger = $data["trigger"];
            $messageArray = $data["messageArray"];
            $messageString = $data["messageString"];

            $command = $messageArray[0];

            switch($command) {
                case "start":
                    $this->step[$fromID] = 0;
                    $msg = "Welcome to the application process, once you're ready to go to the next question, type !app next, if you need to edit a previous type !app previous. To send in an answer do: !app answer < your answer >";
                    $this->discord->api("channel")->messages()->create($channelID, $msg);
                    break;
                case "answer":
                    break;
                case "next":
                    break;
                case "previous":
                    break;
                case "submit":
                    break;
                default:
                    $msg = "**Error:** only use start, answer, next, previous or submit.";
                    $this->discord->api("channel")->messages()->create($channelID, $msg);
                    break;
            }
            //$this->logger->info("Sending <> info to {$channelName} on {$guildName}");
            //$this->discord->api("channel")->messages()->create($channelID, $msg);
        }
    }

    /**
     * @return array
     */
    function information()
    {
        return array(
            "name" => "app",
            "trigger" => array("!app"),
            "information" => "Sends an application to the corp in question!"
        );
    }

        /**
         * @param $msgData
         */
        function onMessageAdmin($msgData)
        {
        }

}
