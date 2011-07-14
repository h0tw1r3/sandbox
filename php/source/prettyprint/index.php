<?php

// Description: 
// Note: Security implications have not been explored, use at your own risk!
//       To generate hash: md5sum `readlink -e <filename>` | md5sum
// Author: Jeffrey Clark

define('SEPERATOR', '|');
define('ALLOWEDWEBPATH', '/sandbox/');

// Define defaults
$file_hash = $recv_hash = '';
$holder_name = $_SERVER['HTTP_HOST'];
$holder_link = '//'.$_SERVER['HTTP_HOST'];
$search_name = '/author: ([\w\s]+)$/i'; // FIXME: implement
$show_copyright = FALSE;
$copyright_years = $modified_year = date('Y');
$error_message = "File Not Found";
$request = html_entity_decode($_SERVER['QUERY_STRING']);
$matches = array();

// Grab filepath and hash if supplied
if (preg_match('/^(\/.+)\|([a-f0-9]{32})$/',$request,$matches)) {
    $filepath = $matches[1];
    $recv_hash = $matches[2];
} else {
    $filepath = $request;
}

// Don't respond to kiddies
if (strpos($filepath,'../') !== FALSE) {
    exit;
}

$recv_filepath = realpath($_SERVER['DOCUMENT_ROOT'] . $filepath);

if (file_exists($recv_filepath) && is_file($recv_filepath)) {
  $file_info = pathinfo($recv_filepath);

  // If hash received and matches, then allow request
  if (!empty($recv_hash)) {
    $file_md5 = md5_file($recv_filepath);
    $file_hash = md5($file_md5."  $recv_filepath\n");
    if ($recv_hash == $file_hash) {
      $error_message = "";
    }
  // Let everything in ALLOWED path through
  } else {
    $pos = strpos($filepath, ALLOWEDWEBPATH);
    if ($pos === 0) {
      $error_message = "";
    }
  }
}

    #<script src="/prettyhtml/google-code-prettify/prettify.js"></script>
    #<link href="/prettyhtml/google-code-prettify/prettify.css" rel="stylesheet" />
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="description" content="return pretty view of a resource" />
    <meta name="tags" content="code highlight" />
    <title>Source: <?= $filepath ?></title>
    <script type="text/javascript" language="javascript" src="http://google-code-prettify.googlecode.com/svn/trunk/src/prettify.js"></script>
    <link href="http://google-code-prettify.googlecode.com/svn/trunk/src/prettify.css" rel="stylesheet" />
    <style>
      html, body { padding:0;margin:.5em; }
      * { font-size: 13px; font-family: Consolas,Tahoma,Arial,serif; }
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
$stat = fstat($fp);
$modified_year = date('Y',$stat['mtime']);
$copyright_years = date('Y',$stat['ctime']);
while($fp && !feof($fp)) {
  echo htmlentities(fread($fp,1024),ENT_NOQUOTES,'UTF-8',FALSE);
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
