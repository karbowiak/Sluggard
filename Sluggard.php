<?php
// More memory allowance
ini_set("memory_limit", "512M");

// Enable garbage collection
gc_enable();

// Just incase we get launched from somewhere else
chdir(__DIR__);

// When the bot started
$startTime = time();

// Require the vendor stuff
require_once(__DIR__ . "/vendor/autoload.php");

// Require the config
if (file_exists(__DIR__ . "/config/config.php"))
    require_once(__DIR__ . "/config/config.php");
else
    throw new Exception("config.php not found (you might wanna start by copying config_new.php)");

// Load the library files (Probably a prettier way to do this that i haven't thought up yet)
foreach (glob(__DIR__ . "/library/*.php") as $lib)
    require_once($lib);

// Init the discord library
$discord = new \Discord\Discord($config["discord"]["email"], $config["discord"]["password"]);
$token = $discord->token();
$gateway = $discord->api("gateway")->show()["url"] . "/"; // need to end in / for it to not whine about it.. *sigh*

// Setup the event loop and logger
$loop = \React\EventLoop\Factory::create();
$logger = new \Zend\Log\Logger();
$writer = new Zend\Log\Writer\Stream("php://output");
$logger->addWriter($writer);

// Check that all the databases are created!
$databases = array("ccpData.sqlite", "sluggard.sqlite");
$databaseDir = __DIR__ . "/database";
if(!file_exists($databaseDir))
    mkdir($databaseDir);
foreach($databases as $db)
    if(!file_exists($databaseDir . "/" . $db))
        touch($databaseDir . "/" . $db);

// Create the sluggard.sqlite tables
$logger->info("Checking for the pressence of the database tables");
updateSluggardDB($logger);
updateCCPData($logger);

// Startup the websocket connection
$client = new \Devristo\Phpws\Client\WebSocket($gateway, $loop, $logger);

// Load the plugins (Probably a prettier way to do this that i haven't thought up yet)
$pluginDirs = array(__DIR__ . "/plugins/tick/*.php", __DIR__ . "/plugins/onMessage/*.php");
$plugins = array();
foreach($pluginDirs as $dir) {
    foreach (glob($dir) as $plugin) {
        require_once($plugin);
        $logger->info("Loading: " . str_replace(".php", "", basename($plugin)));
        $fileName = str_replace(".php", "", basename($plugin));
        $p = new $fileName();
        $p->init($config, $discord, $logger);
        $plugins[] = $p;
    }
}
// Number of plugins loaded
$logger->info("Loaded: " . count($plugins) . " plugins");

// Load all the timers
include(__DIR__ . "/timers.php");

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

$client->on("message", function ($message) use ($client, $logger, $discord, $plugins, $config) {
    // Decode the data
    $data = json_decode($message->getData());

    switch ($data->t) {
        case "READY":
            $logger->notice("Got READY frame");
            $logger->notice("Heartbeat interval: " . $data->d->heartbeat_interval / 1000.0 . " seconds");
            // Can't really use the heartbeat interval for anything, since i can't retroactively change the periodic timers.. but it's usually ~40ish seconds
            //$heartbeatInterval = $data->d->heartbeat_interval / 1000.0;
            break;

        case "MESSAGE_CREATE":
            // Map the data to $data, we don't need all the opcodes and whatnots here
            $data = $data->d;

            // Skip if it's the bot itself that wrote something
            if($data->author->username == $config["bot"]["name"])
                continue;

            // Create the data array for the plugins to use
            $channelData = $discord->api("channel")->show($data->channel_id);
            if ($channelData["is_private"])
                $channelData["name"] = $channelData["recipient"]["username"];

            $msgData = array(
                "isBotOwner" => $data->author->username == $config["discord"]["admin"] || $data->author->id == $config["discord"]["adminID"] ? true : false,
                "message" => array(
                    "lastSeen" => dbQueryField("SELECT lastSeen FROM usersSeen WHERE id = :id", "lastSeen", array(":id" => $data->author->id)),
                    "lastSpoke" => dbQueryField("SELECT lastSpoke FROM usersSeen WHERE id = :id", "lastSpoke", array(":id" => $data->author->id)),
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
            if($data->author->id)
		        dbExecute("REPLACE INTO usersSeen (id, name, lastSeen, lastSpoke, lastWritten) VALUES (:id, :name, :lastSeen, :lastSpoke, :lastWritten)", array(":id" => $data->author->id, ":lastSeen" => date("Y-m-d H:i:s"), ":name" => $data->author->username, ":lastSpoke" => date("Y-m-d H:i:s"), ":lastWritten" => $data->content));

            // Run the plugins
            foreach ($plugins as $plugin) {
                try {
                    $plugin->onMessage($msgData);
                } catch (Exception $e) {
                    $logger->warn("Error: " . $e->getMessage());
                }
            }
            break;

        case "TYPING_START": // When a person starts typing
        case "VOICE_STATE_UPDATE": // When someone switches voice channel (should be used for the sound part i guess?)
        case "CHANNEL_UPDATE": // When a channel gets update
        case "GUILD_UPDATE": // When the guild (server) gets updated
        case "GUILD_ROLE_UPDATE": // a role was updated in the guild
        case "MESSAGE_UPDATE": // a message gets updated, ignore it for now
            //$logger->info("Ignoring: " . $data->t);
            // Ignore them
            break;

        case "PRESENCE_UPDATE": // Update a users status
            if($data->d->user->id) {
                $id = $data->d->user->id;
                $lastSeen = date("Y-m-d H:i:s");
                $lastStatus = $data->d->status;
                $name = $discord->api("user")->show($id)["username"];
                dbExecute("REPLACE INTO usersSeen (id, name, lastSeen, lastStatus) VALUES (:id, :name, :lastSeen, :lastStatus)", array(":id" => $id, ":lastSeen" => $lastSeen, ":name" => $name, ":lastStatus" => $lastStatus));
            }
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
