<?php

$config = array();

$config["bot"] = array(
    "name" => ""
);

$config["database"] = array(
    "host" => "",
    "user" => "",
    "pass" => "",
    "database" => ""
);

$config["discord"] = array(
    "email" => "",
    "password" => "",
    "admin" => "", // The owner of the bot
    "adminID" => "" // The discordID of the owner of the bot);
);

// Twitter
$config["twitter"] = array(
    "consumerKey" => "",
    "consumerSecret" => "",
    "accessToken" => "",
    "accessTokenSecret" => ""
);

$config["eve"] = array(
    "apiKeys" => array(
        "user1" => array(
            "keyID" => ,
            "vCode" => "",
            "characterID" =>
        ),
        "user2" => array(
            "keyID" => ,
            "vCode" => "",
            "characterID" =>
        )
    )
);

// Example from the 4M server
$config["plugins"] = array(
    "periodicTQStatus" => array(
        "channelID" => 118441700157816838
    ),
    "evemails" => array(
        "fromIDs" => array(98047305, 99005805),
        "channelID" => 120639051261804544
    ),
    "fileReader" => array(
        "db" => "/tmp/discord.db",
        "channelConfig" => array(
            "pings" => array(
                "searchString" => false,
                "textStringPrepend" => "@everyone |",
                "textStringAppend" => "",
                "channelID" => 119136919346085888
            ),
            "intel" => array(
                "searchString" => "intel",
                "textStringPrepend" => "",
                "textStringAppend" => "",
                "channelID" => 149918425018400768
            ),
            "blackops" => array(
                "searchString" => "blops",
                "textStringPrepend" => "@everyone |",
                "textStringAppend" => "",
                "channelID" => 149925578135306240
            )
        ),
    ),
    "notifications" => array(
        "channelID" => 149918425018400768
    ),
    "twitterOutput" => array(
        "channelID" => 120474010109607937
    ),
);