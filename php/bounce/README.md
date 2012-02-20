# Bounce Tracker #

Originally I intended this to be a simple URL shortener.  While it does that very well, the tracker is more interesting.

Cookies are typically used to track browsers, but what if cookies are turned off or blocked?  To get around that problem, in addition to sending a cookie it also sends an ETag header.  This provides a much more robust method of tracking clients in cases where clients clear cookies or block cookies.

I have only provides the bare-bones necessary to demonstrate the concept.  No GUI for managing URLs or viewing statistics.  Feel free to contribute patches.

### Database Initialization ###

Because I am lazy you only get sqlite support "out of the box".  Import the sqlite schema and sample data.

    sqlite3 bounce.sqlite3 < sqlite_schema.sql
    sqlite3 bounce.sqlite3 < sqlite_data.sql

### Web Server Configuration ###

Create redirect rules however you like.  I prefer they be as non-intrusive as possible, so I use the query string.

**Apache**: Create/edit .htaccess file in your web root and add the following.

    RewriteCond %{QUERY_STRING} ^([A-Za-z0-9]+)$
    RewriteRule ^$  /bounce_app.php?%1 [L]

**Nginx**: Assuming you have PHP setup, add this to your config.

	location = / {
		if ($args ~ "^([a-z0-9]+)$") {
			rewrite ^/$ /bounce/app.php?$args;
		}
	}

