<?php

// Check if TQs status has changed
$loop->addPeriodicTimer(30, function() use ($logger, $client, $discord, $config) {
    $crestData = json_decode(downloadData("https://public-crest.eveonline.com/"), true);
    $tqStatus = $crestData["serviceStatus"]["eve"];
    $tqOnline = $crestData["userCounts"]["eve"];

    // Store the current status in the permanent cache
    $oldStatus = getPermCache("eveTQStatus");
    if($tqStatus != $oldStatus) {
        $msg = "**New TQ Status:** ***{$tqStatus}*** / ***{$tqOnline}*** users online.";
        $logger->info("TQ Status changed from {$oldStatus} to {$tqStatus}");
        $discord->api("channel")->messages()->create($config["plugins"]["periodicTQStatus"]["channelID"], $msg);
    }
    setPermCache("eveTQStatus", $tqStatus);
});

// Check for an updated database every 12 hours
$loop->addPeriodicTimer(43200, function() use ($logger, $client) {
    $logger->info("Checking for a new update for the CCP database");
    updateCCPData($logger);
});

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
    foreach ($plugins as $plugin) {
        try {
            $plugin->tick();
        } catch (Exception $e) {
            $logger->warn("Error: " . $e->getMessage());
        }
    }
});

// Memory reclamation (30 minutes)
$loop->addPeriodicTimer(1800, function () use ($logger, $client) {
    $logger->info("Memory in use: " . memory_get_usage() / 1024 / 1024 . "MB");
    gc_collect_cycles(); // Collect garbage
    $logger->info("Memory in use after garbage collection: " . memory_get_usage() / 1024 / 1024 . "MB");
});