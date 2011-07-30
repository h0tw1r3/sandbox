<?php

/*
 * Description: apache-like autoindex thing for other web servers
 * Notes: I built this for a quick swap from apache to nginx, even used the
 *        same table structure because I'm just that lazy tonight.
 *     location /sandbox {
 *         rewrite ^(.+).source$ /sandbox/php/source/htmlsource/htmlsource.php last;
 *         index /sandbox/.autoindex/nginx.php;
 *     }
 * Author: Jeffrey Clark
 */

// Config
define('DATE_FORMAT', 'd-M-Y H:i:s T');
define('FOLLOW_SYMLINKS', FALSE); // Not supported yet.
define('IGNORE_REGEX', '/^(.*~|.*#|.*\.git|RCS|CVS|.*,v|.*,t|.*\.log|.*\.swp|\.autoindex)$/');

// Prefix for all path operations, strip garbage
define('CLEAN_REQUEST_URI', str_replace('/../', '/', urldecode($_SERVER['REQUEST_URI'])));
define('INDEX_PATH', $_SERVER['DOCUMENT_ROOT'] . CLEAN_REQUEST_URI);

// Open path and build array of file stats
$curdir = dir(INDEX_PATH);
while (($entry_name = $curdir->read()) !== FALSE) {
    if ($entry_name == '.') {
        continue;
    }

    if (preg_match(IGNORE_REGEX, $entry_name)) {
        continue;
    }

    $path = INDEX_PATH.$entry_name;
    $entries[$entry_name] = lstat($path);
    $entry =& $entries[$entry_name];

    //TODO: Write this!
    if (is_link(INDEX_PATH.$entry_name)) {
        unset($entries[$entry_name]);
        continue;
        $entry['type'] = 'link';
    }

    $entry['type'] = 'file';
    $entry['name'] = $entry_name;
    $entry['uri'] = CLEAN_REQUEST_URI.urlencode($entry_name);
    if (is_dir($path)) {
        $entry['type'] = $entry['icon'] = 'folder';
        if ($entry_name == '..') {
            $entry['icon'] = 'back';
            $entry['name'] = 'Parent Directory';
            $entry['uri'] = dirname(CLEAN_REQUEST_URI);
        }
        if (substr($entry['uri'], -1) !== '/') {
            $entry['uri'] .= '/';
        }
    } else {
        $entry['icon'] = pathinfo($entry_name, PATHINFO_EXTENSION);
        if (!file_exists(dirname(__FILE__).'/icons/'.$entry['icon'].'.png')) {
            $entry['icon'] = 'generic';
        }
    }
}
$curdir->close();
unset($entry);

// Sort by name
ksort($entries);

// Recursive function returns short file size
function reduce_size($intval, $base = 0) {
    static $increment = 1024;
    static $types = array('', 'K', 'M', 'G', 'T');

    return ($intval <= $increment) ? $intval.$types[$base] : reduce_size(round($intval/$increment, 0), ++$base);
}

// Output HTML
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>Index of <?php echo CLEAN_REQUEST_URI; ?></title>
        <link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/3.3.0/build/cssreset/reset-min.css" />
        <link href="http://google-code-prettify.googlecode.com/svn/trunk/src/prettify.css" rel="stylesheet" />
        <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js" type="text/javascript"></script>
        <script src="http://wmd-new.googlecode.com/hg/showdown.js" type="text/javascript"></script>
        <script type="text/javascript" language="javascript" src="http://google-code-prettify.googlecode.com/svn/trunk/src/prettify.js"></script>
        <link rel="stylesheet" type="text/css" href="/sandbox/.autoindex/index-style.css" />
    </head>
    <body>
<?php 
$fh = fopen(dirname(__FILE__).'/HEADER.shtml','r');
if ($fh) {
    while (($line = fgets($fh, 4096)) !== FALSE) {
        if (strstr($line, '<!--#include virtual="${SCRIPT_URL}README.md" -->') !== FALSE) {
            @readfile(INDEX_PATH.'README.md');
        } else {
            echo $line;
        }
    }
    fclose($fh);
}
?>
    <table>
        <tr>
            <th>&nbsp;</th>
            <th>Name</th>
            <th>Last modified</th>
            <th>Size</th>
        </tr>
        <tr>
            <th colspan="4"><hr /></th>
        </tr>
<?php
$entry_pf = '<tr><td valign="top"><img src="/sandbox/.autoindex/icons/%s.png" alt="[%s]" /></td><td><a href="%s">%s</a></td><td align="right">%s</td><td align="right">%s</td></tr>';
reset($entries);
foreach ($entries as $entry_name => $entry) {
    printf($entry_pf, 
        $entry['icon'],
        $entry['type'],
        $entry['uri'],
        htmlentities($entry['name']), 
        $entry['type'] !== 'folder' ?  date(DATE_FORMAT, $entry['mtime']) : '',
        $entry['type'] !== 'folder' ? reduce_size($entry['size']) : ''
    );
}
?>
        <tr><th colspan="4"><hr /></th></tr>
    </table>
<?php readfile(dirname(__FILE__).'/FOOTER.shtml'); ?>
    </body>
</html>
