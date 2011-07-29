<?php

/*
 * Description: Script utilizes Bounce class to track and redirect URLs
 * Notes: Import schema into database first and set DSN respectively
 *     Apache .htaccess config
 *        RewriteCond %{QUERY_STRING} ^([A-Za-z0-9]+)$
 *        RewriteRule ^$  /bounce_app.php?%1 [L]
 * Author: Jeffrey Clark
 */

define('BOUNCE_DSN',   'sqlite:' . dirname(__FILE__) . '/bounce.sqlite3');

require_once 'bounce.php';

# Bounce::debug_request_log();

Bounce::setPDO(new PDO(BOUNCE_DSN));

$bounce = Bounce::To($_SERVER['QUERY_STRING']);

if (!$bounce) {
    echo file_get_contents('./bounce-error.html');
} elseif(!$bounce->findItem()) {
    echo file_get_contents('./bounce-invalid.html');
} else {
    if (!$bounce->findClicker()) {
        $bounce->createClicker();
    }
    if (!$bounce->go()) {
        echo file_get_contents('./bounce-disabled.html');
    }
}
