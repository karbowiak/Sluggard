<?php

// Require the vendor stuff
require_once(__DIR__ . "/vendor/autoload.php");

// Require the config
if (file_exists(__DIR__ . "/config/config.php"))
    require_once __DIR__ . "/config/config.php";
else
    throw new Exception("config.php not found (you might wanna start by copying config_new.php)");

// Init the discord library
$discord = new \Discord\Discord($config["discord"]["email"], $config["discord"]["password"]);
// Get the gateway (all we need the discord library for right now anyway, we will map everything from the initial return data to stuff)
$gateway = $discord->websocketGateway() . "/";

// Load the library files
foreach(glob(__DIR__ . "/library/*.php") as $lib)
    require_once($lib);

// Load the plugins
$plugins = array();
foreach(glob(__DIR__ . "/plugins/*.php") as $plugin)
{
    require_once($plugin);
    $fileName = str_replace(".php", "", basename($plugin));
    $p = new $fileName();
    $p->init($config, $discord);
    $plugins[] = $p;
}

// Setup the webscoket connection
$loop = \React\EventLoop\Factory::create();
$logger = new \Zend\Log\Logger();
$writer = new Zend\Log\Writer\Stream("php://output");
$logger->addWriter($writer);
$client = new \Devristo\Phpws\Client\WebSocket($gateway, $loop, $logger);

// Runtime vars
$startTime = time();
$heartbeatInterval = 0;
$authed = false;

// Keep alive timer (Default to 30 seconds heartbeat interval)
$loop->addPeriodicTimer(30, function () use ($logger, $client) {
    $logger->info("Sending keepalive");
    $client->send(
        json_encode(
            array(
                "op" => 1,
                "d" => time())
            ,
            JSON_NUMERIC_CHECK
        )
    );
});

// Plugin tick timer (2 seconds)
$loop->addPeriodicTimer(2, function () use ($logger, $client, $plugins) {
    try {
        foreach($plugins as $plugin)
            $plugin->tick();
    } catch (Exception $e) {
        $logger->err("Error running plugin: " . $e->getMessage());
    }
});

// Setup the connection handlers
$client->on("connect", function () use ($logger, $client) {
    $logger->notice("Connected!");
    $client->send(
        json_encode(
            array(
                "op" => 2,
                "d" => array(
                    "token" => DISCORD_TOKEN,
                    "properties" => array(
                        "\$os" => "linux",
                        "\$browser" => "discord.php",
                        "\$device" => "discord.php",
                        "\$referrer" => "",
                        "\$referring_domain" => ""
                    ),
                    "v" => 3)
            ),
            JSON_NUMERIC_CHECK
        )
    );
});

$client->on("message", function ($message) use ($client, $logger) {
    // Nasty, sorry.
    global $heartbeatInterval, $authed, $plugins;

    // Decode the data
    $data = json_decode($message->getData());

    switch ($data->t) {
        case "READY":
            $logger->info("Got READY frame");
            $logger->info("Heartbeat interval: " . $data->d->heartbeat_interval / 1000.0 . " seconds");
            $heartbeatInterval = $data->d->heartbeat_interval / 1000.0;
            $authed = true;
            break;

        case "MESSAGE_CREATE":
            $msgData = $data->d;

            // Bind a few things to vars for the plugins
            $timestamp = $msgData->timestamp;
            $nonce = $msgData->nonce;
            $id = $msgData->id;
            $content = $msgData->content;
            $channelID = $msgData->channel_id;
            $from = $msgData->author->username;
            $fromID = $msgData->author->id;

            try {
                foreach ($plugins as $plugin)
                    $plugin->onMessage($content, $channelID); // @todo figure out how to send messages using the new discord library or via websocket
            } catch (Exception $e) {
                $logger->err("Error, plugin failed to run: " . $e->getMessage());
            }
            break;
        default:
            $logger->err("Unknown case: " . $data->t);
            break;
    }
    //$logger->notice("Got message: ".$message->getData());
    //$client->close();
});
$client->open()->then(function () use ($logger, $client) {
    $logger->notice("Connection open");
});
$loop->run();