<?php
/**
 * Sets data to the permanent database storage
 * @param string $key
 * @param string $value
 */
function setPermCache($key, $value)
{
    dbExecute("REPLACE INTO storage (`key`, value) VALUES (:key, :value)", array(":key" => $key, ":value" => $value));
}

/**
 * Gets data from the permanent database storage
 * @param  string $key
 * @return string
 */
function getPermCache($key)
{
    return dbQueryField("SELECT value FROM storage WHERE `key` = :key COLLATE NOCASE", "value", array(":key" => $key));
}