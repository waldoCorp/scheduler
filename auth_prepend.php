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
 * File that is prepended to all pages in our "logged_in" direcotry.
 * Ensures that all requests are from a verified source and we are not
 * the target of a fixation attack.
**/

session_start();

// Set up paths for scripts:
$function_path = __DIR__ . "/functions.php/";
require_once $function_path . 'generate_uuidv4.php';


// Then, we check for a session canary, and generate it if it doesn't exist yet
// also create a UUID as
if (!isset($_SESSION['canary'])) {
	session_regenerate_id(true);
	$_SESSION['canary'] = time();
}

// Also, regenerate the ID every 5 minutes:
if ($_SESSION['canary'] < time() - 1800) {
	session_regenerate_id(true);
	$_SESSION['canary'] = time();
}

// On session creation generate a UUID and create temp tables:
if (!isset($_SESSION['uuid'])) {
  $_SESSION['uuid'] = generate_uuid();
}

// Could get $uuid here...
