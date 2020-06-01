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
 * Function for creation of new usage requests
 *
 * Example usage:
 * require_once '../add_new_request.php';
 *
 * add_new_request($netid, $room, $duration, $hazardous);
 *
 *
 *
 * @author Ben Cerjan
 * @param string $netid : user's netid
 * @param string $room : What room do they need?
 * @param datetime $duration : Duration of usage (in hours)
 * @param bool $hazardous : Boolean for if the user is working with anything dangerous
 *
 * returns TRUE if insert was successful

**/

function add_new_request($netid,$room,$duration,$hazardous) {
	// Require table variables:
	require __DIR__ . '/table_variables.php';

	// Include database connection
	require_once __DIR__ . '/db_connect.php';

	// Connect to db
	$db = db_connect();

	// make netid lower case
  $netid = strtolower($netid);

	try {
		$sql = "INSERT INTO $requests_table (netid, room_id, duration, hazardous, test_user)
						VALUES (:netid, :room, :duration, :hazardous, :test_user)";
		$stmt = $db->prepare($sql);
		$stmt->bindValue(':netid', $netid);
		$stmt->bindValue(':room', $room);
		$stmt->bindValue(':duration', $duration);
		$stmt->bindValue(':hazardous', $hazardous);
		$stmt->bindValue(':test_user', $test_user);

		$success = $stmt->execute();
	} catch(PDOException $e) {
		die('ERROR: ' . $e->getMessage() . "\n");
	}

	return $success;
}
