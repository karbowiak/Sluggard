<?php
namespace Sluggard\Lib;

use PDO;
use Sluggard\SluggardApp;

/**
 * Class Db
 * @package Sluggard\Lib
 */
class Db
{
    /**
     * @var SluggardApp
     */
    private $app;
    /**
     * @var PDO
     */
    private $db;
    /**
     * @var log
     */
    private $log;
    /**
     * @var string
     */
    private $dbLocation = BASEDIR . "/config/database/";
    /**
     * @var bool
     */
    public $persistence = true;

    /**
     * Db constructor.
     * @param SluggardApp $app
     * @param string $dbType
     * @param string $dbName
     * @param null $dbHost
     * @param null $dbUser
     * @param null $dbPass
     */
    function __construct(SluggardApp $app, $dbType = "sqlite", $dbName = "sluggard", $dbHost = null, $dbUser = null, $dbPass = null) {
        $this->app = $app;
        $this->log = $app->log;

        $dsn = "";
        switch($dbType) {
            case "sqlite":
                $dsn = "sqlite:" . $this->dbLocation . $dbName . "." . $dbType;
                break;

            case "mysql":
                $dsn = "musql:dbname={$dbName};host={$dbHost}";
                break;
        }

        try {
            $this->db = new PDO($dsn, $dbUser, $dbPass, array(
                    PDO::ATTR_PERSISTENT => false,
                    PDO::ATTR_EMULATE_PREPARES => true,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+00:00',NAMES utf8;",
                )
            );
        } catch(\Exception $e) {
            var_dump("PDO Error: " . $e->getMessage());
        }
    }

    /**
     * @param $query
     * @param array $parameters
     * @return array|bool
     * @throws \Exception
     */
    public function query($query, $parameters = array()) {
        // Sanity check
        if(strpos($query, ";") !== false) {
            throw new \Exception("Semicolons are not allowed in queries. Use parameters instead.");
        }

        try {
            // Prepare the query
            $stmt = $this->db->prepare($query);

            // Execute with parameters
            $stmt->execute($parameters);

            // Check for errors
            if($stmt->errorCode() != 0) {
                return false;
            }

            // Fetch the results into an associative array
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Close the cursor
            $stmt->closeCursor();

            // Return the data
            return $result;
        } catch(\Exception $e) {
            throw new \Exception("PDO Query Error: " . $e->getMessage());
        }
    }

    /**
     * @param $query
     * @param $field
     * @param array $parameters
     * @return null
     * @throws \Exception
     */
    public function queryField($query, $field, $parameters = array()) {
        // Get the result
        $result = $this->query($query, $parameters);

        // Check if it has results
        if(count($result) == 0) {
            return null;
        }

        // Bind the first result row to $resultRow
        $resultRow = $result[0];

        // Return the result + the field requested
        return $resultRow[$field];
    }

    /**
     * @param $query
     * @param array $parameters
     * @return array
     * @throws \Exception
     */
    public function queryRow($query, $parameters = array()) {
        // Get the result
        $result = $this->query($query, $parameters);

        // Check for any results
        if(count($result) >= 1) {
            return $result[0];
        }

        // There are no results
        return array();
    }

    /**
     * @param $query
     * @param array $parameters
     * @param bool $returnID
     * @return bool|int|string
     * @throws \Exception
     */
    public function execute($query, $parameters = array(), $returnID = false) {
        try {
            if(stristr($query, ";")) {
                $explodedQuery = explode(";", $query);
                foreach($explodedQuery as $newQry) {
                    $stmt = $this->db->prepare($newQry);
                    $stmt->execute($parameters);
                }
            }
            else {
                // Prepare the query
                $stmt = $this->db->prepare($query);

                // Execute with parameters
                $stmt->execute($parameters);
            }

            // Check for errors
            if($stmt->errorCode() != 0) {
                $this->db->rollBack();
                return false;
            }

            // Get the ID of what we just inserted, if it exists
            $returnID = $returnID ? $this->db->lastInsertId() : 0;

            // Row count that was changed
            $rowCount = $stmt->rowCount();

            // Close the cursor
            $stmt->closeCursor();

            if($returnID) {
                return $returnID;
            }

            return $rowCount;
        } catch(\Exception $e) {
            throw new \Exception("PDO Query Error: " . $e->getMessage());
        }
    }

    /**
     *
     */
    public function skipAutoLoad() {}
}