<?php

/**
 * Class item
 */
class item
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
            $item = $data["messageString"];

            if (is_numeric($item))
                $data = dbQueryRow("SELECT * FROM invTypes WHERE typeID = :item", array(":item" => $item));
            else
                $data = dbQueryRow("SELECT * FROM invTypes WHERE typeName = :item", array(":item" => $item));

            if ($data) {
                $msg = "```";
                foreach ($data as $key => $value)
                    $msg .= $key . ": " . $value . "\n";
                $msg .= "```";
                $this->logger->info("Sending item information info to {$channelName} on {$guildName}");
                $this->discord->api("channel")->messages()->create($channelID, $msg);
            }
        }
    }

    /**
     * @return array
     */
    function information()
    {
        return array(
            "name" => "item",
            "trigger" => array("!item"),
            "information" => "Shows information on an item by name or id. Example: **!item raven** or **!item 638**"
        );
    }
}
