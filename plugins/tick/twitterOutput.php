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
        $this->channelID = $config["plugins"]["twitterOutput"]["channelID"]; // outputs to the news channel on the 4M server
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
                $data = $this->twitter->load(Twitter::ME_AND_FRIENDS, 5);
                foreach ($data as $message) {
                    $text = (array)$message->text;
                    $createdAt = (array)$message->created_at;
                    $postedBy = (array)$message->user->name;
                    $screenName = (array)$message->user->screen_name;
                    $id = (int)$message->id;
                    $this->lastID = getPermCache("twitterLatestID"); // get the last posted ID

                    if ($id <= $this->lastID)
                        continue;

                    $this->maxID = max($id, $this->maxID);

                    $url = "https://twitter.com/" . $screenName[0] . "/status/" . $id;
                    $message = array("message" => $text[0], "postedAt" => $createdAt[0], "postedBy" => $postedBy[0], "screenName" => $screenName[0], "url" => $url . $id[0]);
                    $msg = "**@" . $screenName[0] . "** (" . $message["postedBy"] . ") / " . htmlspecialchars_decode($message["message"]);
                    $messages[$id] = $msg;

                    $continue = true;

                    if (sizeof($data))
                        setPermCache("twitterLatestID", $this->maxID);
                }
            } catch (Exception $e) {
                //$this->logger->err("Twitter Error: " . $e->getMessage()); // Don't show there was an error, it's most likely just a rate limit
            }

            if ($continue == true) {
                ksort($messages);

                foreach ($messages as $id => $msg) {
                    $this->discord->api("channel")->messages()->create($this->channelID, $msg);
                    sleep(1); // Lets sleep for a second, so we don't rage spam
                }
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
