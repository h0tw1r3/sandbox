<?php

// Define the REQUEST_URI to remove from the request
// and how to split what is interesting.
define('STRIP_URI', '/sandbox/php/serverfault/295664/calc/');
define('REGEX_URI', '/^(?P<calc>.+)(?P<ext>\.(txt|sci))?$/U');

// Extend to prevent injection attacks
$clean_request_uri = rawurldecode(str_replace(STRIP_URI, '', $_SERVER['REQUEST_URI']));

$math = array();
preg_match(REGEX_URI, $clean_request_uri, $math);

// Perform checks to make sure math is valid?
if (!isset($math['calc'])) {
    die('Invalid calculation');
}

echo $math['calc'] . "<br/>";
echo $math['ext'] . "<br/>";

