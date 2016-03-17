<?php
use Discord\WebSockets\Event;

// Define the current dir
define("BASEDIR", __DIR__);
define("PLUGINDIR", __DIR__ . "/plugins/");

// In case we started from a different directory.
chdir(BASEDIR);

// Load all the vendor files
require_once(BASEDIR . "/vendor/autoload.php");

$cmd = new \Commando\Command();
$cmd->beepOnError();
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

$args = $cmd->getFlagValues();

// Define the path for the logfile
$configPath = $args["c"] ? $args["c"] : \Exception("Error, config file not loaded");

// Allow up to 1GB memory usage.
ini_set("memory_limit", "1024M");

// Turn on garbage collection
gc_enable();

// Bot start time
$startTime = time();

// Load in the config
require_once($configPath);

// define the bots name
define("BOTNAME", strtolower($config["bot"]["botName"]));

// Start the bot, and load up all the Libraries and Models
include(BASEDIR . "/src/init.php");

// Check if the CCP Database exists
if(!file_exists(BASEDIR . "/config/database/ccpData.sqlite"))
    throw new \Exception("Error, ccpData.sqlite does not exist. Please start the bot with --ccp, to update it");

// --ccp was passed, lets update the database!
if($args["ccp"]) {
    $app->ccpdatabaseupdater->createCCPDB();
    echo "Updated the CCP Database, now exiting, please start the bot without --ccp";
    exit();
}

/** @var \Discord\WebSockets\WebSocket $websocket */
/** @var \Sluggard\SluggardApp $app */
/** @var \Discord\Discord $discord */
$websocket->on("ready", function () use ($websocket, $app, $discord, $plugins) {
    $app["log"]->notice("Connection Opened");

    // Update our presence!
    $discord->updatePresence($websocket, "I am the vanguard of your destruction", false);

    // Run the onStart plugins
    foreach ($plugins["onStart"] as $plugin) {
        try {
            $plugin->onStart();
        } catch (\Exception $e) {
            $app->log->debug("Error: " . $e->getMessage());
        }
    }

    // Run the Tick plugins
    $websocket->loop->addPeriodicTimer(1, function () use ($plugins, $app) {
        foreach ($plugins["onTick"] as $plugin) {
            try {
                $plugin->onTick();
            } catch (\Exception $e) {
                $app->log->err("Error: " . $e->getMessage());
            }
        }
    });

    $pluginRunTime = array();
    $websocket->loop->addPeriodicTimer(1, function () use ($plugins, &$pluginRunTime, $app) {
        // Run all the onTimer plugins here and pass along the list of plugins
        foreach ($plugins["onTimer"] as $plugin) {
            $timerFrequency = $plugin->information()["timerFrequency"];
            $pluginName = $plugin->information()["name"];

            // If the currentTime is larger or equals lastRunTime + timerFrequency for this plugin, we'll run it again
            if (time() >= (@$pluginRunTime[$pluginName] + $timerFrequency)) {
                try {
                    $pluginRunTime[$pluginName] = time();
                    $plugin->onTimer();
                } catch (\Exception $e) {
                    $app->log->debug("Error: " . $e->getMessage());
                }
            }
        }
    });
});

// Silly replies to do
$websocket->on(Event::MESSAGE_CREATE, function($msgData, $botData) use ($app, $discord, $websocket, $plugins){
    $message = $msgData->content;

    // Silly replies always to be done..
    if($message == '(╯°□°）╯︵ ┻━┻')
        $msgData->reply('┬─┬﻿ ノ( ゜-゜ノ)');

    // Run the logfile generator
    $app->logfile->writeToLog($app->composemsgdata->data($msgData, $botData));
});

/** @var \Discord\WebSockets\WebSocket $websocket */
/** @var \Sluggard\SluggardApp $app */
/** @var \Discord\Discord $discord */
// On a message, run the plugin that is triggered
foreach($plugins["onMessage"] as $plugin) {
    $websocket->on(Event::MESSAGE_CREATE, function ($msgData, $botData) use ($app, $discord, $websocket, $plugin) {
        $message = $msgData->content;
        $info = $plugin->information();
        $triggered = $app->triggercommand->containsTrigger($message, $info["trigger"]);

        // I'm so triggered right now ........................... ok bad joke
        if($triggered) {
            $app->log->info("Received Message From: {$msgData->author->username}. Message: {$msgData->content}");

            // Add this user and it's data to the usersSeen table
            if ($msgData->author->id)
                $app->sluggarddata->execute("REPLACE INTO usersSeen (id, name, lastSeen, lastSpoke, lastWritten) VALUES (:id, :name, :lastSeen, :lastSpoke, :lastWritten)", array(":id" => $msgData->author->id, ":lastSeen" => date("Y-m-d H:i:s"), ":name" => $msgData->author->username, ":lastSpoke" => date("Y-m-d H:i:s"), ":lastWritten" => $msgData->content));

            // Get the msgData object
            $msgData = $app->composemsgdata->data($msgData, $botData);

            // Run the plugin!
            try {
                $plugin->onMessage($msgData);
            } catch (\Exception $e) {
                $app->log->debug("Error: " . $e->getMessage());
            }
        }
    });
}

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
$websocket->on("close", function ($websocket, $discord) use ($app) {
    $app->log->err("Connection was closed.");
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

// Setup the cache, and use redis..
\Discord\Cache\Cache::setCache(new \Discord\Cache\Drivers\RedisCacheDriver("127.0.0.1", 6379, null, 1));

// Start the bot
$websocket->run();