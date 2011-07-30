## Mod_rewrite Not Working with URL Encoded Values ##

Author was trying to work around encoding problems with Apache rewrite.  My suggestion is to remove Apache and mod_rewrite from the equation completely.

The first answer I provided was quickly dismissed because it suffered from similar issues.  Since I was bored waiting for breakfast this morning, I decided to whip up a correct solution.

### Setup ###

Since I use Nginx, this will work for any web server with rewrite or aliasing support.  Including IIS I would imagine.  Simply alias or internal rewrite the request to wherever you put the api.php script.  For example, with Nginx, this is the rewrite rule I used.

    rewrite ^/sandbox/php/serverfault/295664/calc/ /sandbox/php/serverfault/295664/api.php last;

The only relevant part to configure is STRIP_URI in calc.php.  Obviously it is set for the sandbox enviornment here.

### Try it ###

[2+2][1], [2/2][2], [9*(8/2)][3]  

[1]: calc/2+2.txt
[2]: calc/2/2.txt
[3]: calc/9*(8/2).txt

