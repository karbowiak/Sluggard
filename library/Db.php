<?php

/**
 * @param null $db
 * @return null|PDO
 */
function openDB($db = null)
{
    if($db == null)
        $db = __DIR__ . "/../database/sluggard.sqlite";
    if($db == "ccp")
        $db = __DIR__ . "/../database/ccpData.sqlite";

    $dsn = "sqlite:$db";
    try {
        $pdo = new PDO($dsn, "", "", array(
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_EMULATE_PREPARES => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            )
        );
    } catch (Exception $e)
    {
        var_dump($e->getMessage());
        $pdo = null;
        return $pdo;
    }

    return $pdo;
}

/**
 * @param $query
 * @param $field
 * @param array $params
 * @param null $db
 * @return null|void
 */
function dbQueryField($query, $field, $params = array(), $db = null)
{
    $pdo = openDB($db);
    if($pdo == NULL)
        return;

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    $pdo = null;

    if(sizeof($result) == 0) return null;

    $resultRow = $result[0];
    return $resultRow[$field];
}

/**
 * @param $query
 * @param array $params
 * @param null $db
 * @return null|void
 */
function dbQueryRow($query, $params = array(), $db = null)
{
    $pdo = openDB($db);
    if($pdo == NULL)
        return;

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    $pdo = null;

    if(sizeof($result) >= 1) return $result[0];
    return null;
}

/**
 * @param $query
 * @param array $params
 * @param null $db
 * @return array|void
 */
function dbQuery($query, $params = array(), $db = null)
{
    $pdo = openDB($db);
    if($pdo == NULL)
        return;

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    $pdo = null;

    return $result;
}

/**
 * @param $query
 * @param array $params
 * @param null $db
 */
function dbExecute($query, $params = array(), $db = null)
{
    $pdo = openDB($db);
    if($pdo == NULL)
        return;

    // This is ugly, but, yeah..
    if(stristr($query, ";")) {
        $explodedQuery = explode(";", $query);
        foreach($explodedQuery as $newQry) {
            $stmt = $pdo->prepare($newQry);
            $stmt->execute($params);
        }
        $stmt->closeCursor();
        $pdo = null;
    } else {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $stmt->closeCursor();
        $pdo = null;
    }
}