<?php

// Just incase we get launched from somewhere else
chdir(__DIR__);

// Require the vendor stuff
require_once(__DIR__ . "/vendor/autoload.php");

// Require the config
if (file_exists(__DIR__ . "/config/config.php"))
    require_once __DIR__ . "/config/config.php";
else
    throw new Exception("config.php not found (you might wanna start by copying config_new.php)");

// Init the discord library
$discord = new \Discord\Discord($config["discord"]["email"], $config["discord"]["password"]);
$token = $discord->token["token"];
$gateway = $discord->api("gateway")->show()["url"] . "/";

// Setup the webscoket connection
$loop = \React\EventLoop\Factory::create();
$logger = new \Zend\Log\Logger();
$writer = new Zend\Log\Writer\Stream("php://output");
$logger->addWriter($writer);
$client = new \Devristo\Phpws\Client\WebSocket($gateway, $loop, $logger);

// Load the library files (Probably a prettier way to do this that i haven't thought up yet)
foreach (glob(__DIR__ . "/library/*.php") as $lib)
    require_once($lib);

// Load the plugins (Probably a prettier way to do this that i haven't thought up yet)
$plugins = array();
foreach (glob(__DIR__ . "/plugins/*.php") as $plugin) {
    require_once($plugin);
    $fileName = str_replace(".php", "", basename($plugin));
    $p = new $fileName();
    $p->init($config, $discord, $logger);
    $plugins[] = $p;
}

// Keep alive timer (Default to 30 seconds heartbeat interval)
$loop->addPeriodicTimer(30, function () use ($logger, $client) {
    //$logger->info("Sending keepalive"); // schh
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

// Plugin tick timer (1 second)
$loop->addPeriodicTimer(1, function () use ($logger, $client, $plugins) {
    foreach ($plugins as $plugin)
        $plugin->tick();
});

// Setup the connection handlers
$client->on("connect", function () use ($logger, $client, $token) {
    $logger->notice("Connected!");
    $client->send(
        json_encode(
            array(
                "op" => 2,
                "d" => array(
                    "token" => $token,
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

$client->on("message", function ($message) use ($client, $logger, $discord, $plugins) {
    // Decode the data
    $data = json_decode($message->getData());

    switch ($data->t) {
        case "READY":
            $logger->info("Got READY frame");
            $logger->info("Heartbeat interval: " . $data->d->heartbeat_interval / 1000.0 . " seconds");
            // Can't really use the heartbeat interval for anything, since i can't retroactively change the periodic timers.. but it's usually ~40ish seconds
            //$heartbeatInterval = $data->d->heartbeat_interval / 1000.0;
            //$authed = true;
            break;

        case "MESSAGE_CREATE":
            $data = $data->d;

            // Create the data array for the plugins to use
            $channelData = $discord->api("channel")->show($data->channel_id);
            if($channelData["is_private"])
                $channelData["name"] = $channelData["recipient"]["username"];

            $msgData = array(
                "message" => array(
                    "lastSeen" => null, // Fix functions for this
                    "lastSpoke" => null, // Fix functions for this
                    "timestamp" => $data->timestamp,
                    "id" => $data->id,
                    "message" => $data->content,
                    "channelID" => $data->channel_id,
                    "from" => $data->author->username,
                    "fromID" => $data->author->id,
                    "fromDiscriminator" => $data->author->discriminator,
                    "fromAvatar" => $data->author->avatar
                ),
                "channel" => $channelData,
                "guild" => $channelData["is_private"] ? array("name" => "private conversation") : $discord->api("guild")->show($channelData["guild_id"])
            );

            // Update the users status
            dbExecute("INSERT INTO users (id, name, lastSeen, lastSpoke, lastWritten) VALUES (:id, :name, :lastSeen, :lastSpoke, :lastWritten) ON DUPLICATE KEY UPDATE lastSeen = :lastSeen, lastSpoke = :lastSpoke, lastWritten = :lastWritten", array(":id" => $data->author->id, ":lastSeen" => date("Y-m-d H:i:s"), ":name" => $data->author->username, ":lastSpoke" => date("Y-m-d H:i:s"), ":lastWritten" => $data->content));

            // Run the plugins
            foreach ($plugins as $plugin)
                $plugin->onMessage($msgData);

            break;

        case "TYPING_START": // When a person starts typing
        case "VOICE_STATE_UPDATE": // When someone switches voice channel (should be used for the sound part i guess?)
        case "CHANNEL_UPDATE": // When a channel gets update
        case "GUILD_UPDATE": // When the guild (server) gets updated
            // Ignore them
            break;

        case "PRESENCE_UPDATE": // Update a users status
            $id = $data->d->user->id;
            $lastSeen = date("Y-m-d H:i:s");
            $lastStatus = $data->d->status;
            $name = $discord->api("user")->show($id)["username"];
            dbExecute("INSERT INTO users (id, name, lastSeen, lastStatus) VALUES (:id, :name, :lastSeen, :lastStatus) ON DUPLICATE KEY UPDATE lastSeen = :lastSeen, lastStatus = :lastStatus", array(":id" => $id, ":lastSeen" => $lastSeen, ":name" => $name, ":lastStatus" => $lastStatus));
            break;

        default:
            $logger->err("Unknown case: " . $data->t);
            break;
    }
});
$client->open()->then(function () use ($logger, $client) {
    $logger->notice("Connection open");
});
$loop->run();