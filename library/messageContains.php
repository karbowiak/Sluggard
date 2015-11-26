<?php

function command($message, $trigger)
{
    $trigger = $trigger . " ";
    if(stripos($message, $trigger) === false)
        return false;
    return true;
}

function stringEndsWith($whole, $end)
{
    return @(strpos($whole, $end, strlen($whole) - strlen($end)) !== false);
}

function stringStartsWith($whole, $end)
{
    if(substr($whole, 0, strlen($end)) == $end)
    {
        return true;
    }
    return false;
}