<?php

/*
 * Description: Script utilizes Bounce class to track and redirect URLs
 * Notes: Import schema into database first and set DSN respectively
 *     Apache .htaccess config
 *        RewriteCond %{QUERY_STRING} ^([A-Za-z0-9]+)$
 *        RewriteRule ^$  /bounce_app.php?r=%1 [L]
 * Author: Jeffrey Clark
 */

define('BOUNCE_DSN',   'sqlite:' . dirname(__FILE__) . '/bounce.sqlite3');

require_once 'bounce.php';

# Bounce::debug_request_log();

Bounce::setPDO(new PDO(BOUNCE_DSN));

$bounce = Bounce::To(isset($_REQUEST['r']) ? $_REQUEST['r'] : '');

if (!$bounce || !$bounce->findItem()) {
    // Optional: Redirect to an error page.
    echo "invalid id";
} else {
    if (!$bounce->findClicker()) {
        $bounce->createClicker();
    }
    $bounce->go();
}

