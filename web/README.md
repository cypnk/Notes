Work in progress...

Web version software requirements:
* [PHP](https://www.php.net/manual/en/install.php) version 8.1 or above with the following modules enabled or installed
	* [iconv](https://www.php.net/manual/en/intro.iconv.php)
	* [mbstring](https://www.php.net/manual/en/intro.mbstring.php)
	* [intl](https://www.php.net/manual/en/intro.intl.php)
	* [PDO](https://www.php.net/manual/en/intro.pdo.php) and the [SQLite PDO driver](https://www.php.net/manual/en/ref.pdo-sqlite.php)
* [SQLite](https://sqlite.org/index.html) version 3.39 or above, which comes with some operating systems, but may also need to be installed
* A webserver capable of url rewriting, such as [Nginx](https://nginx.org/en/), [Apache](https://httpd.apache.org/), [httpd(8)](https://man.openbsd.org/httpd) or similar

Required in this folder:
* [/lib](https://github.com/cypnk/Notes/tree/main/web/lib) Main code folder
* [/public](https://github.com/cypnk/Notes/tree/main/web/public) Content served to visitors as-is (includes basic stylehsheet and [Tachyons CSS](https://tachyons.io/))
* [/bootstrap.php](https://github.com/cypnk/Notes/blob/main/web/bootstrap.php) Class loader and base configuration
* [/index.php](https://github.com/cypnk/Notes/blob/main/web/index.php) Web root index file

Extras:
* [/pf.conf.sample](https://github.com/cypnk/Notes/blob/main/web/pf.conf.sample) Example [pf firewall](https://man.openbsd.org/pf) configuration if using this particular firewall on [OpenBSD](https://www.openbsd.org/)
* [/apache-httpd.conf.sample](https://github.com/cypnk/Notes/blob/main/web/apache-httpd.conf.sample) Example Apache virtual host configuration
* [/.htaccess](https://github.com/cypnk/Notes/blob/main/web/.htaccess) Example Apache rewrite directives (use httpd.conf instead whenever possible)
* [/nginx.config.sample](https://github.com/cypnk/Notes/blob/main/web/nginx.config.sample) Example Nginx web server configuration
* [/openbsd-httpd.conf.sample](https://github.com/cypnk/Notes/blob/main/web/openbsd-httpd.conf.sample) Example httpd(8) configuration on OpenBSD
* [/openbsd-tls-httpd.conf.sample](https://github.com/cypnk/Notes/blob/main/web/openbsd-tls-httpd.conf.sample) Example httpd(8) configuration when hosting Notes over TLS on OpenBSD with the [ACME client](https://man.openbsd.org/acme-client.1)
