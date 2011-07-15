<?php @require_once($_SERVER['DOCUMENT_ROOT'] . '/sandbox/php/source/prettyhtml/inc-source.php');

$upload_dir = "./upload";
$max_file_size = "80000";

if (isset($_FILES['file'])) {
    unset($_REQUEST['view']);
    switch($_FILES['file']['error']) {
    case UPLOAD_ERR_OK:
        $tmp_name = $_FILES['file']['tmp_name'];
        $name = $_FILES['file']['name'];

        /* check to make sure we received what we requested  */
        if ($_POST['MAX_FILE_SIZE'] === $max_file_size) {
            if (is_uploaded_file($tmp_name) && move_uploaded_file($tmp_name, "$upload_dir/$name")) {
                $_REQUEST['view'] = $name;
                break;
            }
        }
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
        <!-- catches when a "nice" client tries to send a big (wrong) file //-->
        <!-- worthless against script kiddies unless you double check it on post //-->
        <input type="hidden" name="MAX_FILE_SIZE" value="<?= $max_file_size ?>" />
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

