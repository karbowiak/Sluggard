<?php
namespace Sluggard\Lib;

use Sluggard\SluggardApp;

class gitRevision
{
    private $app;

    public function __construct(SluggardApp &$app) {
        $this->app = $app;
    }

    public function getRevision() {
        exec('git describe --always', $version_mini_hash);
        exec('git rev-list HEAD | wc -l', $version_number);
        exec('git log -1', $line);
        $version["short"] = "v0.".trim($version_number[0]).".".$version_mini_hash[0];
        $version["full"] = "v0.".trim($version_number[0]).".$version_mini_hash[0] (".str_replace('commit ','',$line[0]).")";
        $version["lastChangeDate"] = trim(str_replace("Date:", "", $line[2]));
        return $version;
    }

}