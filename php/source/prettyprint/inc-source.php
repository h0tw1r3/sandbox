<?php

// Author: Jeffrey Clark

ob_start('rewrite_source',1);

function rewrite_source($buffer) {
  static $inject_jquery = '<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js" type="text/javascript"></script>';

  static $inject_head = <<<EOD
<script type="text/javascript">
  $(document).ready(function() {
    $('body').append('<div id="prettyhtml_toggle">view source</div>');
    $('body').append('<div id="prettyhtml_block"></div>');

    $('#prettyhtml_toggle').click(function() {
      if (!($('#prettyhtml_source')[0])) {
          $('#prettyhtml_block').append('<iframe id="prettyhtml_source" src="index.php?%s"></iframe>');
      }
      $(window).trigger('resize');
      $('#prettyhtml_block').toggle(1,function() {
        var a = $('#prettyhtml_toggle');
        if (a.text() == 'view source') {
          a.html('hide source');
        } else {
          a.html('view source');
        }
      });
      return false;
    });
  });
  $(window).resize(function() {
    var a = $('#prettyhtml_source');
    if (a[0]) {
      a.width($('#prettyhtml_block').width()-40);
      a.height($('#prettyhtml_block').height()-40);
    }
  });

</script>
<style>
  #prettyhtml_block { display: none; top: 0; left: 0; position: fixed; width: 100%%; height: 100%%; background: rgba(0, 0, 0, 0.6); }
  #prettyhtml_toggle { cursor: default; z-index: 9; position: fixed; bottom: 0; right: 0; padding: 2px 6px; background: #a00; color: white; font-weight: 900; font-size: 11px; font-family: monospace; text-transform: uppercase; -webkit-border-top-left-radius: 6px; -moz-border-radius-topleft: 6px; border-top-left-radius: 6px;}
  #prettyhtml_source { position: fixed; top: 20px; right: 20px; border: 1px solid black; background: white; }
</style>
EOD;

  if (!empty($inject_head)) {
    if (preg_match('/<script.*src=["\'].*jquery(\.min)?\.js["\'].*><\/script>/',$buffer) > 0) {
      $inject_jquery = '';
    }
    $pos = stripos($buffer,'</head>');
    if ($pos !== FALSE) {
      if (!empty($inject_jquery)) {
        $inject_head = $inject_jquery."\n".$inject_head;
      }
      $inject_head = sprintf($inject_head,$_SERVER['PHP_SELF']);
      $buffer = substr_replace($buffer,$inject_head,$pos,0);
      $inject_head = $inject_jquery = NULL;
    }
  }

  return $buffer;
}

