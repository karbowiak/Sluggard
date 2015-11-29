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
        $systems = dbQuery("SELECT solarSystemName, solarSystemID FROM mapSolarSystems");
        foreach($systems as $system) {
            $this->solarSystems[strtolower($system["solarSystemName"])] = $system["solarSystemID"];
            $this->triggers[] = "!" . strtolower($system["solarSystemName"]);
        }
        $this->triggers[] = "!pc";
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

        $data = command(strtolower($message), $this->information()["trigger"]);
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

            if (stristr($itemName, "plex") || stristr($itemName, "30 day")) {
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

            $systemID = $systemName == "pc" ? "pc" : $this->solarSystems[$systemName];
            if ($continue == true) {
                if ($systemID == "pc") // Global search
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

    /**
     * @return array
     */
    function information()
    {
        return array(
            "name" => "price",
            "trigger" => $this->triggers,
            "information" => "Shows price information for items in EVE. Global prefix: **!pc** System prefix: **!jita** (Replace jita with any system name in EVE) Example: **!pc raven** or **!jita raven**"
        );
    }
}