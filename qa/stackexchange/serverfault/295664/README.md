# Mod_rewrite Not Working with URL Encoded Values #

Author was trying to work around encoding problems with Apache rewrite.  My suggestion is to remove Apache and mod_rewrite from the equation completely.

The first answer I provided was quickly dismissed because it suffered from similar issues.  Since I was bored waiting for breakfast this morning, I decided to whip up a correct solution.

### Setup ###

Since I use Nginx, this will work for any web server with rewrite or aliasing support.  Including IIS I would imagine.  Simply alias or internal rewrite the request to wherever you put the api.php script.  For example, with Nginx, this is the rewrite rule I used.

    rewrite ^/sandbox/(.+)/calc/ /sandbox/$1/api.php last;

The only relevant part to configure is STRIP_URI in calc.php.  Obviously it is set for the sandbox enviornment here.

#### Issue with Apache ####

The .htaccess file included will get you 99% there.  One problem you will likely run into is how Apache handles encoded slashes "/".  By default Apache decodes them before any path processing.  Seems stupid that [AllowEncodedSlashes][4] defaults to Off, but it is easy to work around.  Simply add:

    AllowEncodedSlashes On

To the VirtualHost configuration.  Sorry, but it does not work in an .htaccess file.

### Try it ###

[2+2][1], [2/2][2], [9*(8/2)][3]  

[1]: calc/2+2.txt
[2]: calc/2/2.txt
[3]: calc/9*(8/2).txt
[4]: http://httpd.apache.org/docs/2.0/mod/core.html#allowencodedslashes
