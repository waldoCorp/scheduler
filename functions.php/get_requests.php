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


/**
 * Function to get all currently stored requests
 *
 * Example usage:
 * require_once '../get_requests.php';
 *
 * $requests = get_requests();
 *
 *
 *
 * @author Ben Cerjan
 *
 * returns multidimensional array of the form: array(0=>[netid, room_id, duration, hazardous], ...)
 *

**/

function get_requests() {
	// Require table variables:
	require __DIR__ . '/table_variables.php';

	// Include database connection
	require_once __DIR__ . '/db_connect.php';

	// Connect to db
	$db = db_connect();


	try {
		$sql = "SELECT * from $requests_table WHERE test_user = 'public' OR
						test_user = :test_user";

		$stmt = $db->prepare($sql);
		$stmt->bindValue(':test_user', $test_user);

		$success = $stmt->execute();
	} catch(PDOException $e) {
		die('ERROR: ' . $e->getMessage() . "\n");
	}

	$result = $stmt->fetchAll();
	return $result;
}
