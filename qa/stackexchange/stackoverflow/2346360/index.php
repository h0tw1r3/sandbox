<?php @require_once($_SERVER['DOCUMENT_ROOT'] . '/sandbox/php/source/prettyprint/inc-source.php');

if (!empty($_GET)) sleep(2);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="description" content="example of using jquery validate plugin with blockui" />
    <meta name="tags" content="jquery validate blockui example" />
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js" type="text/javascript"></script>
    <script src="http://ajax.aspnetcdn.com/ajax/jquery.validate/1.8.1/jquery.validate.min.js"></script>
    <script src=".external/jquery.blockUI.min.js"></script>
    <title>Example: jQuery with validate and blockui plugins</title>
    <style>
      #source { border: 1px solid black; }
    </style>
    <script type="text/javascript">
      $(document).ready(function() {
        $('#form1').validate({
          rules: {
            fieldone: { required: true },
            fieldtwo: { required: true }
          },
          submitHandler: function(form) {
            $(form).block();
            form.submit();
          }
        });

        $('input:checkbox[name=toggleone]').click(function() {
          if ($(this).is(':checked')) {
            $('input[name=fieldone]').rules('add', { required: true });
          } else {
            $('input[name=fieldone]').rules('remove');
          }
        });

        $('#altsubmit').click(function() {
          $('input[name=fieldtwo]').rules('remove');
          $('#form1').submit();
        });
      });
    </script>
  </head>
  <body>
    <div class="content">
      <h1>Example: jQuery with validate and blockui plugins</h1>
      <form id="form1">
        <label for="fieldone">Field One</label>
        <input type="text" name="fieldone" /><br/>
        <label for="fieldtwo">Field Two</label>
        <input type="text" name="fieldtwo" /><br/>
        <label for="toggleone">Toggle Field One Required</label>
        <input type="checkbox" name="toggleone" checked="checked" /><br/>
        <!-- _No form entity should be named 'submit'_
             Otherwise the form submit method will be overridden //-->
        <button name="send" type="submit">Submit</button>
      </form>
      <button id="altsubmit">Change Rules And Submit</button>
    </div>
    <div class="time"><?php print strftime('%c'); ?></div>
  </body>
</html>
