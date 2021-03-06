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
 * Function to delete a request based on all parameters (netid, room, duration,
 * and hazardous). Will only delete one if there is more than one matching event.
 *
 * Example usage:
 * require_once '../delete_request.php';
 *
 * delete_request($netid, $room, $duration, $hazardous);
 *
 *
 *
 * @author Ben Cerjan
 * @param string $netid : user's netid
 * @param string $room : What room do they need?
 * @param datetime $duration : Duration of usage (in hours)
 * @param bool $hazardous : Boolean for if the user is working with anything dangerous
 *
 * returns TRUE if delete was successful

**/

function delete_request($netid,$room,$duration,$hazardous) {
	// Require table variables:
	require __DIR__ . '/table_variables.php';

	// Include database connection
	require_once __DIR__ . '/db_connect.php';

	// Connect to db
	$db = db_connect();

	// make netid lower case
  $netid = strtolower($netid);

	// Need to do the min(request_id) because it is possible that people have
	// multiple requests for the same room/duration/... but we should only delete
	// one at a time

	try {
		$sql = "DELETE FROM $requests_table WHERE request_id
						IN (SELECT min(request_id) FROM $requests_table WHERE
						netid = :netid AND room_id = :room AND duration = :duration
						AND hazardous = :hazardous AND
						(test_user = 'public' OR test_user = :test_user))";

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
