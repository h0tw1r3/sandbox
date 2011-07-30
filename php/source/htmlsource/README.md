## Source to HTML Colorizer ##

Colorizes any text file.  When setup properly, it works just like mod_php's application/x-httpd-php-source support or 'php -s' from the command line.

### Apache Instructions ###

1.  Copy htmlsource.php to your web root.
2.  Change the constants in htmlsource.php to fit your needs.
3.  Enable mod_rewrite and create a .htaccess file similar to:

        RewriteEngine On
        RewriteRule .*\.source$ /htmlsource.php [L]

### Nginx Instructions ###

Pretty much the same as Apache, just a different rewrite rule.

    location / {
        rewrite ^(.*).source$ /htmlsource.php last;
    }

