<?php
/**
 *    Copyright (c) 2020 Ben Cerjan, Lief Esbenshade
 *
 *    This file is part of Scheduler.
 *
 *    Scheduler is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU Affero General Public License as published $
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    Scheduler is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with Scheduler.  If not, see <https://www.gnu.org/licenses/>.
**/

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
