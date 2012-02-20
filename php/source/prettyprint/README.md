# Embed source link in PHP output #

Uses output buffering to inject jquery into the output of a PHP script.  jQuery creates a "view source" button in the lower right of the viewport.  Upon clicking jquery creates an floating iframe and displays the result of a special prettify script.

Currently only supprts output that contains a head tag.  Should be easy to fix.
