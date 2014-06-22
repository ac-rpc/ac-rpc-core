## Research Project Calculator Installation

**Note:** The Research Project Calculator _Classic_ is no longer supported

### Mailing List
Please [|join the RPC-development mailing list](http://lists.minitex.umn.edu/mailman/listinfo/rpc-development) for information about new releases, bugfixes, etc. It is a very low-traffic list.

### System Requirements
#### Server Requirements
 - A web server (Apache, IIS, etc) running PHP 5.1.2 or later  ''Only UNIX/Apache information is supplied in this guide.''
 - A MySQL 5.0+ database
 - The ability to run cron jobs or scheduled tasks
 - The ability to send mail from PHP using the mail() function

#### PHP Requirements & Dependencies
 - PHP 5.1.2 or later
 - PHP PECL::Package::json needed for PHP < 5.3.0 (`json_encode()` and `json_decode()` are native to PHP 5.3) http://pecl.php.net/package/json
 - PHP MySQLi extension (usually distributed as part of PHP)
 - PHP Smarty 3 templating engine http://www.smarty.net/

### Installation
Begin by extracting the current Research Project Calculator release archive to your web server.

#### Database setup
 - Create a new MySQL database. We recommend naming it `'rpc'`.
 - Run the database initialization script (`install/rpc-mysql.sql`).

**From the mysql console:**

```
# Create the database...
mysql> CREATE DATABASE newrpc2;
Query OK, 1 row affected (0.04 sec)

# Make it active...
mysql> USE rpc;
Database changed

# Run the initialization script...
mysql> source install/rpc-mysql.sql
Query OK, 0 rows affected, 1 warning (0.00 sec)
Query OK, 0 rows affected, 1 warning (0.00 sec)
...
...
Query OK, 0 rows affected (0.00 sec)

# Verify the tables were created...
mysql> SHOW TABLES;
+----------------------+
| Tables_in_rpc        |
+----------------------+
| assignments          |
| assignments_brief_vw |
| linked_assignments   |
| linked_steps         |
| linked_steps_vw      |
| native_sessions      |
| steps                |
| steps_vw             |
| templates_vw         |
| users                |
+----------------------+
10 rows in set (0.00 sec)
```
#### Application setup on the web server
 - RPC ships with a default .htaccess file for Apache.  Edit `.htaccess` if you need to, particularly if you need to change `RewriteBase` to a path other than the root.
 - Make sure PHP Smarty and PECL::json (if necessary) are installed and working in PHP.
 - Copy the `rpc/` directory to its web-accessible location on your web server.
 - Set the correct permissions on `rpc/tmpl/compile/`. It must be writable by the web server user. On a Unix/Linux/Apache server, this is typically:

```
# Assuming user "apache", might be "httpd" or "apache2"
chown apache rpc/tmpl/compile
chmod 700 rpc/tmpl/compile
```
 - Setup a cron job to run nightly emailing scripts

```
crontab -e
# Run scripts/rpc_reminders.php each night at 12:01
1 0 * * * /path/to/installation/rpc/scripts/rpc_reminders.php
```
### Application Configuration
Research Project Calculator configuration is stored in `inc/config.inc.php`.

 - Rename `inc/example_config.inc.php` to 'inc/config.inc.php`
 - Edit config.inc.php.  The `DB_*` directives are most important, almost all will require some attention and changes.  Documentation on each directive is available in the example config file.
 - Make sure you've identified a username/email address in 'AUTH_SUPERUSER'!

### Get Started
 - Login as the address specified in `AUTH_SUPERUSER`
 - No assignment templates have been created in your installation. You can create them manually, or if you would like to copy the ones used by Minitex, [contact Minitex](https://www.minitex.umn.edu/Contact/) with a message sent to the attention of Link Swanson.

### User Types & Administration
 - [User type descriptions and administration](useradmin.md)

## Advanced Topics
 - [Skins & Theming](skins.md)
 - [Custom authentication plugins](authplugins.md)
 - URL Rewriting (not yet documented)
