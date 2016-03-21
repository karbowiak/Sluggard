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

// Load all the vendor files
require_once(BASEDIR . "/vendor/autoload.php");

$cmd = new \Commando\Command();
// Define the logfiles location
$cmd->option("c")
    ->aka("config")
    ->title("Path to configuration file")
    ->describedAs("Defines the configuration file that the bot will use to run")
    ->file(true)
    ->require();

$cmd->option("ccp")
    ->title("Install the ccp database")
    ->describedAs("Installs the CCP database. Required before the bot can start properly")
    ->boolean();

$cmd->option("conv")
    ->aka("convert")
    ->title("Converts a username/password login to token login")
    ->describedAs("Converts the username/password login for Discord, to the new bot API token login");

$args = $cmd->getFlagValues();

// Define the path for the logfile
$configPath = $args["c"] ? $args["c"] : \Exception("Error, config file not loaded");

// Bot start time
$startTime = time();

// Load in the config
require_once($configPath);

// define the bots name
define("BOTNAME", strtolower($config["bot"]["botName"]));

// Conversion path triggered
if($args["conv"]) {
    $discord = new \Discord\Discord($config["discord"]["email"], $config["discord"]["password"]);
    $bot = Discord::createOauthApp($args["conv"], $config["bot"]["botName"]);
    $discord->convertToBot($args["conv"], $bot->id, $bot->secret);
    echo "Bot has been converted to the new Token API..";
    exit();
}

// Start the bot, and load up all the Libraries and Models
include(BASEDIR . "/src/init.php");

// Check if the CCP Database exists
if (!file_exists(BASEDIR . "/config/database/ccpData.sqlite") || filesize(BASEDIR . "/config/database/ccpData.sqlite") == 0)
    throw new \Exception("Error, ccpData.sqlite does not exist. Please start the bot with --ccp, to update it");

// --ccp was passed, lets update the database!
if ($args["ccp"]) {
    $app->ccpdatabaseupdater->createCCPDB();
    echo "Updated the CCP Database, now exiting, please start the bot without --ccp";
    exit();
}

/** @var \Discord\WebSockets\WebSocket $websocket */
/** @var \Sluggard\SluggardApp $app */
/** @var \Discord\Discord $discord */
$websocket->on("ready", function () use ($websocket, $app, $discord, $plugins) {
    $app->log->notice("Connection Opened");

    // Update our presence!
    $discord->updatePresence($websocket, $app->config->get("presenceStatus", "bot", "god"), false);

    // Run the onStart plugins
    foreach ($plugins["onStart"] as $plugin)
        $plugin->onStart();

    // Run the Tick plugins
    $websocket->loop->addPeriodicTimer(1, function () use ($plugins, $app) {
        foreach ($plugins["onTick"] as $plugin)
            $plugin->onTick();
    });

    $pluginRunTime = array();
    $websocket->loop->addPeriodicTimer(1, function () use ($plugins, &$pluginRunTime, $app) {
        // Run all the onTimer plugins here and pass along the list of plugins
        foreach ($plugins["onTimer"] as $plugin) {
            $timerFrequency = $plugin->information()["timerFrequency"];
            $pluginName = $plugin->information()["name"];

            // If the currentTime is larger or equals lastRunTime + timerFrequency for this plugin, we'll run it again
            if (time() >= (@$pluginRunTime[$pluginName] + $timerFrequency)) {
                $pluginRunTime[$pluginName] = time();
                $plugin->onTimer();
            }
        }
    });
});

// Silly replies to do
$websocket->on(Event::MESSAGE_CREATE, function ($msgData, $botData) use ($app, $discord, $websocket, $plugins) {
    $message = $msgData->content;

    // Silly replies always to be done..
    if ($message == '(╯°□°）╯︵ ┻━┻') {
        $channel = \Discord\Parts\Channel\Channel::find($msgData->channel_id);
        $channel->sendMessage('┬─┬﻿ ノ( ゜-゜ノ)');
    }

    // Run the logfile generator
    if ($msgData->author->username != $app->config->get("botName", "bot"))
        $app->logfile->writeToLog($app->composemsgdata->data($msgData, $botData));
});

/** @var \Discord\WebSockets\WebSocket $websocket */
/** @var \Sluggard\SluggardApp $app */
/** @var \Discord\Discord $discord */
// On a message, run the plugin that is triggered
$websocket->on(Event::MESSAGE_CREATE, function ($msgData, $botData) use ($app, $discord, $websocket, $plugins) {
    // Map the message content to $message for easier usage
    $message = $msgData->content;

    // Show every message in the bot window (Except if it's from ourself, then ignore it!
    if($msgData->author->username != $app->config->get("botName", "bot"))
        $app->log->info("Received Message From: {$msgData->author->username}. Message: {$message}");

    // Add this user and it's data to the usersSeen table
    if ($msgData->author->id)
        $app->sluggarddata->execute("REPLACE INTO usersSeen (id, name, lastSeen, lastSpoke, lastWritten) VALUES (:id, :name, :lastSeen, :lastSpoke, :lastWritten)", array(":id" => $msgData->author->id, ":lastSeen" => date("Y-m-d H:i:s"), ":name" => $msgData->author->username, ":lastSpoke" => date("Y-m-d H:i:s"), ":lastWritten" => $msgData->content));

    // what is the trigger symbol?
    $triggerSymbol = $app->config->get("trigger", "bot", "!");

    // Does the message contain a trigger in the first place?
    // Does the message contain a trigger in the first place?
    $triggered = $app->triggercommand->containsTrigger($message, $triggerSymbol);

    // The message contains a trigger, lets find the plugin !
    if ($triggered) {
        foreach ($plugins["onMessage"] as $plugin) {
            // Load plugin information so we can figure out what it's trigger is
            $info = $plugin->information();

            // Check if what was written actually triggers this plugin
            $triggered = $app->triggercommand->containsTrigger($message, $info["trigger"]);

            // A plugin was triggered!
            if ($triggered) {
                // Get the msgData object
                $msgData = $app->composemsgdata->data($msgData, $botData);

                // Run the plugin!
                $plugin->onMessage($msgData);
            }
        }
    }
});

/** @var \Discord\WebSockets\WebSocket $websocket */
/** @var \Sluggard\SluggardApp $app */
/** @var \Discord\Discord $discord */
// Handle presence updates
$websocket->on(Event::PRESENCE_UPDATE, function ($userData) use ($app, $discord, $websocket, $plugins) {
    if ($userData->user->id && $userData->user->username) {
        $lastSeen = date("Y-m-d H:i:s");
        $lastStatus = $userData->status;
        $name = $userData->user->username;
        $id = $userData->user->id;
        $app->sluggarddata->execute("REPLACE INTO usersSeen (id, name, lastSeen, lastStatus) VALUES (:id, :name, :lastSeen, :lastStatus)", array(":id" => $id, ":lastSeen" => $lastSeen, ":name" => $name, ":lastStatus" => $lastStatus));
    }
});

// Handle close event (Not exactly gracefully, but consider it handled...
/** @var \Discord\WebSockets\WebSocket $websocket */
/** @var \Sluggard\SluggardApp $app */
$websocket->on("close", function ($websocket, $reason, $discord) use ($app) {
    $app->log->err("Connection was closed: " . $reason);
    die();
});

// Handle close event (Not exactly gracefully, but consider it handled...
/** @var \Discord\WebSockets\WebSocket $websocket */
/** @var \Sluggard\SluggardApp $app */
$websocket->on("error", function ($error, $websocket) use ($app) {
    $app->log->err("Error: {$error}");
});

// Handle reconnect event
/** @var \Sluggard\SluggardApp $app */
$websocket->on("reconnect", function () use ($app) {
    $app->log->info("Reconnecting to Discord");
});

// Handle reconnected event
/** @var \Sluggard\SluggardApp $app */
$websocket->on("reconnected", function () use ($app) {
    $app->log->info("Reconnected to Discord");
});

// Setup the cache (Only works aslong as the bot is running)
\Discord\Cache\Cache::setCache(new \Discord\Cache\Drivers\ArrayCacheDriver());

// Add some config options to Guzzle..
\Discord\Helpers\Guzzle::addGuzzleOptions(array("http_errors" => false, "allow_redirects" => true));

// Start the bot
$websocket->run();