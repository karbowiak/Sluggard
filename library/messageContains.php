<?php

/**
 * @param $message
 * @param $trigger
 * @return array|bool
 */
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