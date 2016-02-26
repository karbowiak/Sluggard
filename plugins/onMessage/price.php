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

            $single = dbQueryRow("SELECT typeID, typeName FROM invTypes WHERE typeName = :item COLLATE NOCASE", array(":item" => ucfirst($itemName)), "ccp");
            $multiple = dbQuery("SELECT typeID, typeName FROM invTypes WHERE typeName LIKE :item LIMIT 5 COLLATE NOCASE", array(":item" => "%" . ucfirst($itemName) . "%"), "ccp");

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
                $this->discord->api("channel")->messages()->create($channelID, "**Multiple results found:** {$items}");
            }

            // If there is a single result, we'll get data now!
            if($single) {
                $typeID = $single["typeID"];
                $typeName = $single["typeName"];

                // Get pricing data
                $data = new SimpleXMLElement(downloadData("https://api.eve-central.com/api/marketstat?usesystem=30000142&typeid={$typeID}"));
                $lowBuy = number_format((float) $data->marketstat->type->buy->min ,2);
                $avgBuy = number_format((float) $data->marketstat->type->buy->avg ,2);
                $highBuy = number_format((float) $data->marketstat->type->buy->max ,2);
                $lowSell = number_format((float) $data->marketstat->type->sell->min ,2);
                $avgSell = number_format((float) $data->marketstat->type->sell->avg ,2);
                $highSell = number_format((float) $data->marketstat->type->sell->max ,2);

                $this->logger->info("Sending pricing info to {$channelName} on {$guildName}");
                $messageData = "**{$typeName}** (Jita) - **Buy:** (Avg: {$avgBuy} / High: {$highBuy}) / **Sell:** (Low: {$lowSell} / Avg: {$avgSell})";
                $this->discord->api("channel")->messages()->create($channelID, $messageData);
            }
            else {
                $this->discord->api("channel")->messages()->create($channelID, "**Error:** ***{$itemName}*** not found");
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
}