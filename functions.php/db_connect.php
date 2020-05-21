<?php

/** Code to facilitate connections to the database.
 *  Returns the handle to the database connection.
 *  Example usage:
 *  <code>
 *  require_once './functions.php/db_connect.php';
 *  <code>
 *
 *  Use of function:
 *  $db = db_connect();
 *
 * @author Ben Cerjan
 * (No parameters)
 * @return db_handle : Returns handle to database for PDO usage.
**/

function db_connect() {
        // Include Config Files
        include __DIR__ . '/../db_config.php';

        // Connect to Database
        try {
                $conn = new PDO("pgsql: user=$dbUser dbname=$dbName");
                $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
                // Terminate when exception thrown
                die('ERROR: ' . $e->getMessage() . "\n");
        }

        // Return connection if successful
        return $conn;
}
