<?php

/**
 *
 * Function to generate UUIDv4's for use as identifiers
 *
 * for reference: https://stackoverflow.com/a/15875555
 *
 * return: a string of characters that is a valid v4 UUID.
 *
**/

function generate_uuid() {

  $data = random_bytes(16);
  $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
  $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

  return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

}
