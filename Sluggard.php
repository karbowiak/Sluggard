<?php
use Discord\WebSockets\Event;

// Allow up to 1GB memory usage.
ini_set("memory_limit", "1024M");

// Turn on garbage collection
gc_enable();

// Define the current dir
define("BASEDIR", __DIR__);
define("PLUGINDIR", __DIR__ . "/plugins/");

// In case we started from a different directory.
chdir(BASEDIR);

// Bot start time
$startTime = time();

// Load all the vendor files
require_once(BASEDIR . "/vendor/autoload.php");

// Load in the config
if(file_exists(BASEDIR . "/config/config.php"))
    require_once(BASEDIR . "/config/config.php");
else
    throw new Exception("config.php not found (you might wanna start by copying config_new.php)");

// Start the bot, and load up all the Libraries and Models
require_once(BASEDIR . "/src/init.php");

//$websocket->loop->addPeriodicTimer(1, function() use ($plugins) {

//});

$websocket->on("ready", function() use ($websocket, $app, $discord, $plugins) {
    $app["log"]->notice("Connection Opened");

    // Run the onStart plugins
    foreach($plugins["onStart"] as $plugin) {
        try {
            $plugin->onStart();
        } catch(\Exception $e) {
            $app->log->debug("Error: " . $e->getMessage());
        }
    }

    // On a message, do all of the following
    $websocket->on(Event::MESSAGE_CREATE, function ($msgData, $botData) use ($app, $discord, $websocket, $plugins) {
        // If i sent the message myself, just ignore it..
        if($msgData->author->username != $app->config->get("botName", "bot")) {
            $app->log->info("Received Message From: {$msgData->author->username}. Message: {$msgData->content}");

            // Add this user and it's data to the usersSeen table
            if($msgData->author->id)
                $app->sluggarddata->execute("REPLACE INTO usersSeen (id, name, lastSeen, lastSpoke, lastWritten) VALUES (:id, :name, :lastSeen, :lastSpoke, :lastWritten)", array(":id" => $msgData->author->id, ":lastSeen" => date("Y-m-d H:i:s"), ":name" => $msgData->author->username, ":lastSpoke" => date("Y-m-d H:i:s"), ":lastWritten" => $msgData->content));

            // Does it contain a trigger? if it does, we'll do all of this expensive shit, otherwise ignore it..
            if ($app->triggercommand->containsTrigger($msgData->content, $app->config->get("trigger", "bot", "!")) == true) {
                $channelData = \Discord\Parts\Channel\Channel::find($msgData["channel_id"]);

                if($channelData->is_private == true)
                    $channelData->setAttribute("name", $msgData->author->username);

                $msgData = (object)array(
                    "isBotOwner" => false,
                    "user" => $msgData,
                    "message" => (object)array(
                        "lastSeen" => $app->sluggarddata->queryField("SELECT lastSeen FROM usersSeen WHERE id = :id", "lastSeen", array(":id" => $msgData->author->id)),
                        "lastSpoke" => $app->sluggarddata->queryField("SELECT lastSpoke FROM usersSeen WHERE id = :id", "lastSpoke", array(":id" => $msgData->author->id)),
                        "timestamp" => $msgData->timestamp->toDateTimeString(),
                        "id" => $msgData->author->id,
                        "message" => $msgData->content,
                        "channelID" => $msgData->channel_id,
                        "from" => $msgData->author->username,
                        "fromID" => $msgData->author->id,
                        "fromDiscriminator" => $msgData->author->discriminator,
                        "fromAvatar" => $msgData->author->avatar
                    ),
                    "channel" => $channelData,
                    "guild" => $channelData->is_private ? (object)array("name" => "private conversation") : \Discord\Parts\Guild\Guild::find($channelData->guild_id),
                    "botData" => $botData
                );

                // Run the plugins!
                foreach ($plugins["onMessage"] as $plugin) {
                    try {
                        $plugin->onMessage($msgData);
                    } catch (\Exception $e) {
                        $app->log->debug("Error: " . $e->getMessage());
                    }
                }
            }
        }
    });
    $websocket->on(Event::PRESENCE_UPDATE, function ($userData) use ($app, $discord, $websocket, $plugins) {
        if($userData->user->id && $userData->user->username) {
            $lastSeen = date("Y-m-d H:i:s");
            $lastStatus = $userData->status;
            $name = $userData->user->username;
            $id = $userData->user->id;
            $app->sluggarddata->execute("REPLACE INTO usersSeen (id, name, lastSeen, lastStatus) VALUES (:id, :name, :lastSeen, :lastStatus)", array(":id" => $id, ":lastSeen" => $lastSeen, ":name" => $name, ":lastStatus" => $lastStatus));
        }
    });
});

/*
 * From Uniquoooo
 * ready = ready packet finished parsing
 * heartbeat = heartbeat sent
 * close = websocket closed
 * error = error on websocket
 * sent-login-frame = when we sent the websocket authentication frame
 * connectfail = when we can't connect to the websocket
 * raw = raw websocket data in json
 * unavailable = when a guild returns unavailable
 *
 * From code:
    const READY = 'READY';
    const PRESENCE_UPDATE = 'PRESENCE_UPDATE';
    const TYPING_START = 'TYPING_START';
    const USER_SETTINGS_UPDATE = 'USER_SETTINGS_UPDATE';
    const VOICE_STATE_UPDATE = 'VOICE_STATE_UPDATE';

    // Guild
    const GUILD_CREATE = 'GUILD_CREATE';
    const GUILD_DELETE = 'GUILD_DELETE';
    const GUILD_UPDATE = 'GUILD_UPDATE';

    const GUILD_BAN_ADD = 'GUILD_BAN_ADD';
    const GUILD_BAN_REMOVE = 'GUILD_BAN_REMOVE';
    const GUILD_MEMBER_ADD = 'GUILD_MEMBER_ADD';
    const GUILD_MEMBER_REMOVE = 'GUILD_MEMBER_REMOVE';
    const GUILD_MEMBER_UPDATE = 'GUILD_MEMBER_UPDATE';
    const GUILD_ROLE_CREATE = 'GUILD_ROLE_CREATE';
    const GUILD_ROLE_UPDATE = 'GUILD_ROLE_UPDATE';
    const GUILD_ROLE_DELETE = 'GUILD_ROLE_DELETE';

    // Channel
    const CHANNEL_CREATE = 'CHANNEL_CREATE';
    const CHANNEL_DELETE = 'CHANNEL_DELETE';
    const CHANNEL_UPDATE = 'CHANNEL_UPDATE';

    // Messages
    const MESSAGE_CREATE = 'MESSAGE_CREATE';
    const MESSAGE_DELETE = 'MESSAGE_DELETE';
    const MESSAGE_UPDATE = 'MESSAGE_UPDATE';
 */
// Start the bot
$websocket->run();