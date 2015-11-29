<?php

class priceChecks
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
            "name" => "priceCheck",
            "trigger" => array("!pc", "!jita", "!amarr", "!rens", "!hek", "!dodixie"),
            "information" => "This is a price fetcher for EVE, you can use !pc for the global market or !jita, !rens, !amarr, !dodixie and !hek for specific trade hubs. eg: !jita raven"
        );
    }

    function tick()
    {

    }

    function onMessage($msgData)
    {
        // Bind a few things to vars for the plugins
        $message = $msgData["message"]["message"];
        $channelName = $msgData["channel"]["name"];
        $guildName = $msgData["guild"]["name"];
        $channelID = $msgData["message"]["channelID"];

        $data = command($message, $this->information()["trigger"]);
        if (isset($data["trigger"])) {
            $systemName = $data["trigger"];
            $itemName = $data["messageString"];
            $typeID = null;
            $typeName = null;
            $continue = false;

            $data = dbQueryRow("SELECT typeID, typeName FROM invTypes WHERE typeName = :item", array(":item" => $itemName));
            if (count($data) != null) {
                $typeID = $data["typeID"];
                $typeName = $data["typeName"];
                $continue = true;
            }

            if(stristr($itemName, "plex") || stristr($itemName, "30 day"))
            {
                $typeID = 29668;
                $typeName = "30 Day Pilot's License Extension (PLEX)";
                $continue = true;
            }

            if ($typeID == null) {
                $itemNames = dbQuery("SELECT typeName FROM invTypes WHERE typeName LIKE :item LIMIT 5", array(":item" => "%" . $itemName . "%"));
                if (count($itemNames) == 0) {
                    $this->discord->api("channel")->messages()->create($channelID, "**Error:** No results found");
                } elseif (count($itemNames) == 1) {
                    $data = dbQueryRow("SELECT typeID, typeName FROM invTypes WHERE typeName = :item", array(":item" => $itemNames[0]["typeName"]));
                    $typeID = $data["typeID"];
                    $typeName = $data["typeName"];
                    $continue = true;
                } else {
                    $items = array();
                    foreach ($itemNames as $itm)
                        $items[] = $itm["typeName"];

                    $items = implode(", ", $items);
                    $this->discord->api("channel")->messages()->create($channelID, "**Error:** Multiple results found: {$items}");
                }
            }

            switch ($systemName) {
                case "jita":
                    $systemID = "30000142";
                    break;
                case "amarr":
                    $systemID = "30002187";
                    break;
                case "rens":
                    $systemID = "30002510";
                    break;
                case "dodixie":
                    $systemID = "30002659";
                    break;
                case "hek":
                    $systemID = "30002053";
                    break;
                default:
                    $systemID = null;
                    break;
            }

            if ($continue == true) {
                if ($systemID == null)
                    $url = "http://api.eve-central.com/api/marketstat?typeid=" . $typeID;
                else
                    $url = "http://api.eve-central.com/api/marketstat?usesystem=" . $systemID . "&typeid=" . $typeID;

                $xml = downloadData($url);
                $data = new SimpleXMLElement($xml);
                $buyPrice = number_format((float)$data->marketstat->type->buy->max, 2, ".", ",");
                $sellPrice = number_format((float)$data->marketstat->type->sell->min, 2, ".", ",");
                $place = "Global";
                if ($systemName != "pc")
                    $place = ucfirst($systemName);

                $this->logger->info("Sending pricing info to {$channelName} on {$guildName}");
                $this->discord->api("channel")->messages()->create($channelID, "{$typeName} ({$place}) - **Buy:** {$buyPrice} ISK / **Sell:** {$sellPrice} ISK");
            }
        }
    }
}