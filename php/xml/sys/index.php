<?php @require_once($_SERVER['DOCUMENT_ROOT'] . '/prettyhtml/inc-source.php');

$upload_dir = "./upload";

if (isset($_FILES['file'])) {
    switch($_FILES['file']['error']) {
    case UPLOAD_ERR_OK:
        $tmp_name = $_FILES['file']['tmp_name'];
        $name = $_FILES['file']['name'];

        move_uploaded_file($tmp_name, "$upload_dir/$name");
        $_REQUEST['view'] = $name;
        break;
    default:
        $output = "Error occured receiving file upload.";
    }
}

if (isset($_REQUEST['view'])) {
  $output = transform($upload_dir . "/" . $_REQUEST['view']);
}

foreach (glob("$upload_dir/*.xml") as $filename) {
  $filename = basename($filename);
  $uploads .= "<li><a href='?view=" . urlencode($filename) . "'>" . htmlentities($filename) . "</a></li>";
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
  <head>
    <link rel="stylesheet" type="text/css" media="screen" href="sys.css" />
  </head>
  <body>
    <div>
      <form method="post" enctype="multipart/form-data">
        <label for="file">File:</label>
        <input type="file" name="file" id="file" />
        <input type="submit" name="submit" value="Submit" />
      </form>
    <br/>
<?= $output ?>
    </div>
    <div class="uploads">
      <div>Uploads</div>
      <ul>
<?= $uploads ?>
      </ul>
    </div>
  </body>
</html>

<?php

function transform($file) {
    $xsl = new DOMDocument('1.0','UTF-8');
    $xsl->load(dirname(__FILE__) . '/lshw.xsl');

    $xml = new DOMDocument('1.0','UTF-8');
    $xml->load($file);

    $proc = new XSLTProcessor();
    $proc->importStylesheet($xsl);

    $result = $proc->transformToXML($xml);

    if (!empty($result)) {
        return $result;
    } else {
        return "Sorry, something bad happened.  Unable to display output at this time.";
    }
}

