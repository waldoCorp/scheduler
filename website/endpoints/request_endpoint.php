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

// Endpoint for adding a request for a room


require_once $function_path . 'add_new_request.php';
$return = $_POST;
$netid = $return['netid'];
$room = $return['room'];
$duration = $return['duration'] . ' hour';

if( is_null($return['hazardous']) ) {
  $hazardous = 0;
} else {
  $hazardous = 1;
}

$num_requests = $_SESSION['num_requests'];

if ($num_requests <= 10) {
  ++$_SESSION['num_requests'];
  add_new_request($netid, $room, $duration, $hazardous);
}

header('Location: ../index.php');
