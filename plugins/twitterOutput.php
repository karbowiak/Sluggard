<?php

/**
 * Class twitterOutput
 */
class twitterOutput
{
    /**
     * @var
     */
    var $config;
    /**
     * @var
     */
    var $discord;
    /**
     * @var
     */
    var $logger;

    /**
     * @var
     */
    var $twitter;
    /**
     * @var
     */
    var $lastCheck;
    /**
     * @var
     */
    var $lastID;
    /**
     * @var
     */
    var $channelID;
    /**
     * @var
     */
    var $maxID;

    /**
     * @param $config
     * @param $discord
     * @param $logger
     */
    function init($config, $discord, $logger)
    {
        $this->config = $config;
        $this->discord = $discord;
        $this->logger = $logger;
        $this->twitter = new Twitter($config["twitter"]["consumerKey"], $config["twitter"]["consumerSecret"], $config["twitter"]["accessToken"], $config["twitter"]["accessTokenSecret"]);
        $this->lastCheck = time();
        $this->maxID = 0;
        $this->channelID = 120474010109607937; // outputs to the news channel on the 4M server
    }

    /**
     *
     */
    function tick()
    {
        $continue = false;
        $data = array();
        // If last check + 60 seconds is larger or equal to the current time(), we run
        if ($this->lastCheck <= time()) {
            // Fetch the last 25 twitter replies and/or searches
            try {
                $data = $this->twitter->load(Twitter::ME_AND_FRIENDS, 25);
                foreach ($data as $message) {
                    $text = (array)$message->text;
                    $createdAt = (array)$message->created_at;
                    $postedBy = (array)$message->user->name;
                    $screenName = (array)$message->user->screen_name;
                    $id = (int)$message->id;
                    $this->lastID = getPermCache("twitterLatestID"); // get the last posted ID

                    $twitterName = $this->config["twitter"]["twitterName"];
                    if ($twitterName == $screenName[0])
                        continue;

                    if ($id <= $this->lastID)
                        continue;

                    $this->maxID = max($id, $this->maxID);

                    $url = "https://twitter.com/" . $screenName[0] . "/status/" . $id;
                    $message = array("message" => $text[0], "postedAt" => $createdAt[0], "postedBy" => $postedBy[0], "screenName" => $screenName[0], "url" => $url . $id[0]);
                    $msg = "**@" . $screenName[0] . "** (" . $message["postedBy"] . ") / " . date("H:i:s", strtotime($message["postedAt"])) . " / " . self::shortenUrl($url) . " / " . htmlspecialchars_decode($message["message"]);
                    $messages[$id] = $msg;

                    $continue = true;
                }
            } catch(Exception $e)
            {
                $this->logger->err("**Error:** " . $e->getMessage());
            }

            if($continue == true) {
                ksort($messages);

                foreach ($messages as $id => $msg)
                    $this->discord->api("channel")->messages()->create($this->channelID, $msg);

                if (sizeof($data))
                    setPermCache("twitterLatestID", $this->maxID);
            }
            $this->lastCheck = time() + 60;
        }
    }

    /**
     * @param $url
     * @return string
     */
    function shortenUrl($url)
    {
        return file_get_contents("http://is.gd/api.php?longurl=" . $url);
    }

    /**
     * @param $msgData
     */
    function onMessage($msgData)
    {
    }

    /**
     * @return array
     */
    function information()
    {
        return array(
            "name" => "",
            "trigger" => array(""),
            "information" => ""
        );
    }
}
