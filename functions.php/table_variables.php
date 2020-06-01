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


/*
 * parametrize table names so we can consistently reference them
 *
 * If there is a session variable, use modified table names based on session
 * UUID
 */

// Define postfixes for our tables:
$users_table = 'users';
$rooms_table = 'room_ids';
$requests_table = 'time_requests';
$schedule_table = 'time_scheduled';
$temp_table = 'temp_tables';

if (isset($_SESSION['uuid'])) {
  $test_user = $_SESSION['uuid'];
} else {
  $test_user = 'public';
}
