<?php

namespace Sluggard\Lib;


use Sluggard\SluggardApp;

/**
 * Class cURL
 * @package Sluggard\Lib
 */
class cURL
{
    /**
     * @var SluggardApp
     */
    private $app;
    /**
     * @var log
     */
    private $log;

    /**
     * cURL constructor.
     * @param SluggardApp $app
     */
    function __construct(SluggardApp &$app)
    {
        $this->app = $app;
        $this->log = $app->log;
    }


    /**
     * @param $url
     * @param array $headers
     * @return bool
     */
    public function getData($url, $headers = array())
    {
        // Md5 the url
        $md5 = md5($url);

        $headers = array_merge($headers, array('Connection: keep-alive', 'Keep-Alive: timeout=10, max=1000'));
        try {
            // Init curl
            $curl = curl_init();
            // Setup curl
            curl_setopt_array($curl, array(
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_USERAGENT => $this->app->config->get("userAgent", "bot", "Discord Bot"),
                CURLOPT_TIMEOUT => 5,
                CURLOPT_POST => false,
                CURLOPT_FORBID_REUSE => false,
                //CURLOPT_ENCODING => '',
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FAILONERROR => true,
            ));

            // Get the data
            $result = curl_exec($curl);

            // Return the data
            return $result;
        } catch (\Exception $e) {
            $this->log->warn("cURL Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @param $url
     * @param $storagePath
     * @return bool
     */
    public function getLargeData($url, $storagePath) {
        try {
            $readHandle = fopen($url, "rb");
            $writeHandle = fopen($storagePath, "w+b");

            if(!$readHandle || !$writeHandle)
                return false;

            while(!feof($readHandle)) {
                if(fwrite($writeHandle, fread($readHandle, 4096)) == FALSE)
                    return false;
            }

            fclose($readHandle);
            fclose($writeHandle);

            return true;
        } catch (\Exception $e) {
            $this->log->warn("cURL Error: " . $e->getMessage());
            return false;
        }
    }
}