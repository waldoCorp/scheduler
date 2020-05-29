<?php


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
  $test_user = ''; 
}
