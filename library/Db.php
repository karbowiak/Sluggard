<?php

function openDB()
{
    global $config;
    $dbname = $config["database"]["database"];
    $dbuser = $config["database"]["user"];
    $dbpass = $config["database"]["pass"];
    $dbhost = $config["database"]["host"];

    $dsn = "mysql:dbname=$dbname;host=$dbhost";
    try {
        $pdo = new PDO($dsn, $dbuser, $dbpass, array(
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
 * Queries the database and returns a single field
 * @param  string $query
 * @param  array  $params
 * @param  string $field
 * @return string
 */
function dbQueryField($query, $field, $params = array())
{
    $pdo = openDB();
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
 * Queries the database and returns a single row
 * @param  string $query
 * @param  array  $params
 * @return array
 */
function dbQueryRow($query, $params = array())
{
    $pdo = openDB();
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
 * Queries the database and returns all results
 * @param  [type] $query
 * @param  array  $params
 * @return array
 */
function dbQuery($query, $params = array())
{
    $pdo = openDB();
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
 * Executes a query to the database
 * @param  string $query
 * @param  array  $params
 */
function dbExecute($query, $params = array())
{
    $pdo = openDB();
    if($pdo == NULL)
        return;

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $stmt->closeCursor();
    $pdo = null;
}