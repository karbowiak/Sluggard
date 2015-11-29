<?php

function stringEndsWith($whole, $end)
{
    return @(strpos($whole, $end, strlen($whole) - strlen($end)) !== false);
}

function stringStartsWith($whole, $end)
{
    if (substr($whole, 0, strlen($end)) == $end) {
        return true;
    }
    return false;
}

function command($message, $trigger)
{
    foreach ($trigger as $trig) {
        if (substr($message, 0, strlen($trig)) == $trig) {
            $data = explode(" ", $message);

            $trig = str_replace("!", "", $data[0]);
            unset($data[0]);
            $data = array_values($data);
            $messageString = implode(" ", $data);

            return array("trigger" => $trig, "messageArray" => $data, "messageString" => $messageString);
        }
    }
    return false;
}