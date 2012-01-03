<?php
/*
 * Description: Shows codez
 * Notes: Apache .htaccess config
 *        RewriteEngine on
 *        RewriteRule (.*).source$ /htmlsource.php [L]
 * Author: Jeffrey Clark
 */

define('ALLOWEDWEBPATH', '/sandbox/'); /* limit source serving to this web folder */
define('SRCSUFFIX', '.source');        /* Try setting this to .phps */
define('SRCSUFFIX_REPLACE', '');       /* and this to .php (also mod .htacces) */
define('SRCMUSTEXIST', FALSE);         /*.source file must exist to be served */

// Define defaults
$holder_name = $_SERVER['HTTP_HOST'];
$holder_link = '//'.$_SERVER['HTTP_HOST'];
$search_name = '/author: ([\w\s]+)$/i'; // FIXME: implement
$show_copyright = FALSE;
$copyright_years = $modified_year = date('Y');
$error_message = "File Not Found";

$request = $_SERVER['REQUEST_URI'];

// Don't respond to kiddies
if (strpos($request,'../') !== FALSE) {
    $request = '';
}

$tmp = urldecode(substr_replace($request,SRCSUFFIX_REPLACE,-(strlen(SRCSUFFIX))));
$recv_filepath = realpath($_SERVER['DOCUMENT_ROOT'].$tmp);

// Does the source file or symlink exist?
if (!SRCMUSTEXIST || (SRCMUSTEXIST && realpath($_SERVER['DOCUMENT_ROOT'].$request))) {
  if (is_file($recv_filepath) && !is_binary_file($recv_filepath)) {
    $file_info = pathinfo($recv_filepath);
    $pos = strpos($request, ALLOWEDWEBPATH);
    if ($pos === 0) {
      $error_message = "";
    }
  }
}

if (!empty($error_message)) {
    if (PHP_SAPI == 'cgi-fcgi') {
        header("Status: 404 Not Found");
    } else {
        header("HTTP/1.0 404 Not Found");
    }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="description" content="return pretty view of a resource" />
    <meta name="tags" content="code highlight" />
    <title>Source: <?= basename($recv_filepath) ?></title>
    <script type="text/javascript" language="javascript" src="http://google-code-prettify.googlecode.com/svn/trunk/src/prettify.js"></script>
    <link href="http://google-code-prettify.googlecode.com/svn/trunk/src/prettify.css" rel="stylesheet" />
    <style>
      html, body { padding:0;margin:.5em; }
      * { font-size: 13px; font-family: Consolas,DejaVu Sans Mono,Monaco,Terminal,Courier New; }
      .watermark { position:fixed; right:16px; bottom:8px; }
    </style>
  </head>
  <body onLoad="prettyPrint()">
<?php if (!empty($error_message)): ?>
    <div class="error">
<?= $error_message ?>
    </div>
<?php else: ?>
<code class="prettyprint" style="white-space:pre"><?php
$fp = fopen($recv_filepath, "r");
$is_utf16 = false;
if (!feof($fp)) {
  $line = fread($fp,128);
  $is_utf16 = is_utf16($line);
  rewind($fp);
}
$stat = fstat($fp);
$modified_year = date('Y',$stat['mtime']);
$copyright_years = date('Y',$stat['ctime']);
while($fp && !feof($fp)) {
  $line = fread($fp,32768);
  if ($is_utf16) {
    $line = utf16_to_utf8($line);
  }
  echo htmlentities($line,ENT_NOQUOTES,'UTF-8',FALSE);
}
fclose($fp);
?></code>
<?php endif;

if ($modified_year != $copyright_years) {
    $copyright_years .= '-'.$modified_year;
}

?>
    <div class="watermark">
    <?php if($show_copyright): ?>&copy; <?= $copyright_years ?><?php endif; ?> <a href="<?= $holder_link ?>" target="_parent"><?= $holder_name ?></a>
    </div>
  </body>
</html>
<?php

function is_binary_file($file) {
  try {
    $fp = fopen($file,'r');
    $block = fread($fp, 512);
    fclose($fp);
  }
  catch(Exception $e) {
    $block = "";
  }
  return is_binary($block);
}

function is_binary($block, $utf = true) {
  $test = (
    0 or substr_count($block, "^ -~")/strlen($block) > 0.3
    or substr_count($block, "\x00") > 0
  ); 
  if ($test && !($utf && is_utf16($block))) {
    return true;
  } else {
    return false;
  }
}

function is_utf16($string) {
  if (is_binary($string, false)) {
    $test = mb_convert_encoding($string, 'UTF-8', 'UTF-16');
    if (strlen($test) > 1) {
      $test2 = mb_convert_encoding($test, 'UTF-16', 'UTF-8');
      $test3 = mb_convert_encoding($test2, 'UTF-8', 'UTF-16');
      if ($test3 == $test) {
        return true;
      }
    }
  }
  return false;
}

function utf16_to_utf8($str) {
    $c0 = ord($str[0]);
    $c1 = ord($str[1]);

    if ($c0 == 0xFE && $c1 == 0xFF) {
        $be = true;
    } else if ($c0 == 0xFF && $c1 == 0xFE) {
        $be = false;
    } else {
        return $str;
    }

    $str = substr($str, 2);
    $len = strlen($str);
    $dec = '';
    for ($i = 0; $i < $len; $i += 2) {
        $c = ($be) ? ord($str[$i]) << 8 | ord($str[$i + 1]) : 
                ord($str[$i + 1]) << 8 | ord($str[$i]);
        if ($c >= 0x0001 && $c <= 0x007F) {
            $dec .= chr($c);
        } else if ($c > 0x07FF) {
            $dec .= chr(0xE0 | (($c >> 12) & 0x0F));
            $dec .= chr(0x80 | (($c >>  6) & 0x3F));
            $dec .= chr(0x80 | (($c >>  0) & 0x3F));
        } else {
            $dec .= chr(0xC0 | (($c >>  6) & 0x1F));
            $dec .= chr(0x80 | (($c >>  0) & 0x3F));
        }
    }
    return $dec;
}
