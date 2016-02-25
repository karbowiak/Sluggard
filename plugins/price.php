<?php

/**
 * Class priceChecks
 */
class price
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
    var $solarSystems;
    var $triggers = array();

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
        $this->triggers = array("!pc", "!jita");
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
        // Bind a few things to vars for the plugins
        $message = $msgData["message"]["message"];
        $channelName = $msgData["channel"]["name"];
        $guildName = $msgData["guild"]["name"];
        $channelID = $msgData["message"]["channelID"];

        // Quick Lookups
        $quickLookUps = array(
            "plex" => array(
                "typeID" => 29668,
                "typeName" => "30 Day Pilot's License Extension (PLEX)"
            ),
            "30 day" => array(
                "typeID" => 29668,
                "typeName" => "30 Day Pilot's License Extension (PLEX)"
            )
        );

        $data = command(strtolower($message), $this->information()["trigger"]);
        if (isset($data["trigger"])) {
            $systemName = $data["trigger"];
            $itemName = $data["messageString"];

            $single = dbQueryRow("SELECT typeID, typeName FROM invTypes WHERE typeName = :item", array(":item" => $itemName));
            $multiple = dbQuery("SELECT typeID, typeName FROM invTypes WHERE typeName LIKE :item LIMIT 5", array(":item" => "%" . $itemName . "%"));

            // Quick lookups
            if(isset($quickLookUps[$itemName]))
                $single = $quickLookUps[$itemName];

            // Sometimes the multiple lookup is returning just one
            if(count($multiple) == 1)
                $single = $multiple[0];

            // If there are multiple results, and not a single result, it's an error
            if(empty($single) && !empty($multiple)) {
                $items = array();
                foreach($multiple as $item)
                    $items[] = $item["typeName"];

                $items = implode(", ", $items);
                $this->discord->api("channel")->messages()->create($channelID, "**Price Error:** Multiple results found: {$items}");
            }

            // If there is a single result, we'll get data now!
            if($single) {
                $typeID = $single["typeID"];
                $typeName = $single["typeName"];

                // Get pricing data
                $priceData = dbQueryRow("SELECT avgSell, avgBuy, lowSell, lowBuy, highSell, highBuy, created FROM invPrices WHERE typeID = :typeID AND avgSell != 0 ORDER BY created DESC", array(":typeID" => $typeID));
                $lowBuy = number_format($priceData["lowBuy"], 2);
                $avgBuy = number_format($priceData["avgBuy"], 2);
                $highBuy = number_format($priceData["highBuy"], 2);
                $lowSell = number_format($priceData["lowSell"], 2);
                $avgSell = number_format($priceData["avgSell"], 2);
                $highSell = number_format($priceData["highSell"], 2);
                $fromDate = $priceData["created"];
                $this->logger->info("Sending pricing info to {$channelName} on {$guildName}");
                $messageData = "{$typeName} (Jita / {$fromDate}) - **Buy:** (Avg: {$avgBuy} / High: {$highBuy}) / **Sell:** (Low: {$lowSell} / Avg: {$avgSell})";
                $this->discord->api("channel")->messages()->create($channelID, $messageData);
            }
            else {
                $this->discord->api("channel")->messages()->create($channelID, "**Price Error:** No item found");
            }
        }
    }

    /**
     * @return array
     */
    function information()
    {
        return array(
            "name" => "price",
            "trigger" => $this->triggers,
            "information" => "Shows price information for items in EVE. Example: **!pc raven**"
        );
    }

    /**
     * @param $msgData
     */
    function onMessageAdmin($msgData)
    {
    }

}