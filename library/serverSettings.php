<?php

function getAllSettingsForServer($serverID) {
    return dbQuery("SELECT * FROM discordServerSettings WHERE serverID = :serverID", array(":serverID" => $serverID));
}

function getSettingForServer($serverID, $key) {
    return dbQueryField("SELECT value FROM discordServerSettings WHERE serverID = :serverID AND `key` = :key", "value", array(":serverID" => $serverID, ":key" => $key));
}

function setSettingForServer($serverID, $key, $value) {
    dbExecute("REPLACE INTO discordServerSettings (serverID, `key`, value) VALUES (:serverID, :key, :value)");
}