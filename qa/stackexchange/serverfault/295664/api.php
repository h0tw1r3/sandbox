<?php

header('Content-type: text/plain');

// Define the REQUEST_URI to remove from the request
// and how to split what is interesting.
define('STRIP_URI', '/^.+\/calc\//U');
define('REGEX_URI', '/^(?P<calc>.+)(\.(?P<ext>[\w]+))?$/U');

// Extend to prevent injection attacks
$found_uri = 0;
$clean_request_uri = rawurldecode(preg_replace(STRIP_URI, '', $_SERVER['REQUEST_URI'], -1, $found_uri));

$math = array();
preg_match(REGEX_URI, $clean_request_uri, $math);

// Perform checks to make sure math is valid?
if (!$found_uri || !isset($math['calc'])) {
    die('Invalid calculation');
}

print_r($math);
