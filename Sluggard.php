<?php
use Discord\WebSockets\Event;

// Allow up to 1GB memory usage.
ini_set("memory_limit", "1024M");

// Turn on garbage collection
gc_enable();

// Define the current dir
define("BASEDIR", __DIR__);

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

$websocket->on("ready", function() use ($websocket, $app, $discord, $plugins) {
    $app["log"]->notice("Sluggard Ready");

    // Run the onStart plugins
    foreach($plugins as $plugin) {
        try {
            $plugin->onStart();
        } catch(\Exception $e) {
            $app->log->debug("Error: " . $e->getMessage());
        }
    }

    // On a message, do all of the following
    $websocket->on(Event::MESSAGE_CREATE, function ($msgData, $botData) use ($app, $discord, $websocket, $plugins) {

        // If i sent the message myself, just ignore it..
        if($msgData->author->username == $app->config->get("botName", "bot"))
            continue;

        // Does it contain a trigger? if it does, we'll do all of this expensive shit, otherwise ignore it..
        if($app->triggerCommand->containsTrigger($msgData->content, $app->config->get("trigger", "bot", "!")) == true) {
            $channelData = \Discord\Parts\Channel\Channel::find($msgData["channel_id"]);

            if ($channelData->is_private)
                $channelData->name = $channelData->recipient->username;

            $msgData = (object)array(
                "isBotOwner" => false,
                "message" => (object)array(
                    "lastSeen" => false,
                    "lastSpoke" => false,
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
            foreach ($plugins as $plugin) {
                try {
                    $plugin->onMessage($msgData);
                } catch (\Exception $e) {
                    $app->log->debug("Error: " . $e->getMessage());
                }
            }
        }
    });
});

/*
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