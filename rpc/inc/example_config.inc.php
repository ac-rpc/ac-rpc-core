<?php
/**
 * Copyright 2010 by the Regents of the University of Minnesota,
 * University Libraries - Minitex
 *
 * This file is part of The Research Project Calculator (RPC).
 *
 * RPC is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * RPC is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with The RPC.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * config.inc.php
 *
 * Global application configuration:
 * Values stored in the $CONF[] array will be used
 *
 * @package RPC
 * to create the application's basic configuration.
 */

/**
 * DB_HOST: Database hostname or IP address (default localhost)
 *
 * *REQUIRED*
 */
$CONF['DB_HOST'] = 'localhost';
/**
 * DP_PORT: Database port (default 3306)
 */
$CONF['DB_PORT'] = 3306;
/**
 * DB_NAME: Database name
 *
 * *REQUIRED*
 */
$CONF['DB_NAME'] = 'YOUR DATABASE NAME';
/**
 * DB_USER: Database username with access to DB_NAME
 *
 * *REQUIRED*
 */
$CONF['DB_USER'] = 'DATABASE USERNAME';
/**
 * DB_PASS: Database password for DB_USER
 */
$CONF['DB_PASS'] = 'DATABASE PASSWORD';
/**
 * AUTH_SUPERUSERS: Comma-separated list of usernames of designated system superusers
 */
$CONF['AUTH_SUPERUSERS'] = 'superuser@example.com';
/**
 * AUTH_PLUGIN: Authentication plugin to use, located in plugins/auth
 */
$CONF['AUTH_PLUGIN'] = 'native';
/**
 * SHIB_ENTITY_IDENTIFIER: Complete HTTP protocol://hostname:port/idp
 * to your Shibboleth Identity Provider's entityID
 * Specify port if non-standard (80 or 443(ssl))
 *
 * *REQUIRED - If using 'shibboleth' for authentication*
 */
$CONF['SHIB_ENTITY_IDENTIFIER'] = "https://example.edu/idp/shibboleth";
/**
 * SHIB_USERNAME_KEY: The shibboleth attribute that identifies your user by username
 * This value will become the logged-in username stored in users.username
 *
 * *REQUIRED - If using 'shibboleth' for authentication*
 */
$CONF['SHIB_USERNAME_KEY'] = 'uid';
/**
 * SHIB_EMAIL_KEY: The shibboleth attribute that maps to your user's email address
 * This value will become the logged-in username stored in users.email
 *
 * *REQUIRED - If using 'shibboleth' for authentication*
 */
$CONF['SHIB_EMAIL_KEY'] = 'mail';
/**
 * SHIB_ALLOW_GUEST: Whether or not to allow guest access
 * If TRUE, users are required to login via Shibboleth in order to access the RPC system
 * If FALSE, users can create assignments but not save or recieve notifications without an account
 * If unspecified, defaults to TRUE
 */
$CONF['SHIB_ALLOW_GUEST'] = TRUE;
/**
 * SESSION_NAME: PHP Session name
 */
$CONF['SESSION_NAME'] = 'RPC';
/**
 * HTTP_HOST: Complete HTTP protocol://hostname:port specification
 * for this installation.
 * If unspecified, defaults to http://hostname
 * Specify port if non-standard (80 or 443(ssl))
 * Only used for CLI notification scripts.  Hostname is dynamically determined for
 * all web scripts.
 */
$CONF['HTTP_HOST'] = "http://www.example.com";
/**
 * RELATIVE_WEB_PATH: HTTP path of the application, relative to your server's document root
 * Note: Do NOT include http://servername or https://servername at the beginning!
 */
$CONF['RELATIVE_WEB_PATH'] = "/path/to/application";
/**
 * DOJO_PATH: HTTP path to required Dojo toolkit, relative to server document root
 *
 * *REQUIRED*
 */
$CONF['DOJO_PATH'] = '/rpc-dojo';
/**
 * USE_URL_REWRITE: Is Apache URL rewriting active? if TRUE, links will take it into
 * account. If FALSE, URLs will use exposed querystrings
 */
$CONF['USE_URL_REWRITE'] = TRUE;
/**
 * APP_LONG_NAME: Long title of this application (appears on user interface, email notifications)
 *
 * *REQUIRED*
 */
$CONF['APP_LONG_NAME'] = 'Research Project Calculator';
/**
 * APP_SHORT_NAME: Short/abbreviated title of this application (appears on user interface, notifications)
 * If not specified, APP_LONG_NAME will be used
 */
$CONF['APP_SHORT_NAME'] = 'RPC';
/**
 * EMAIL_FROM_ADDRESS: When notifications are sent, they come from this email address
 *
 * *REQUIRED*
 */
$CONF['EMAIL_FROM_ADDRESS'] = 'address@example.com';
/**
 * EMAIL_FROM_SENDER_NAME: Human readable sender name for notifications
 */
$CONF['EMAIL_FROM_SENDER_NAME'] = 'NAME OF THE SENDER';
/**
 * NOTIFICATION_ADVANCE_DAYS: Number of days prior to a step's due date that a reminder will be sent
 * Default: 2
 */
$CONF['NOTIFICATION_ADVANCE_DAYS'] = 2;
/**
 * SHORT_DATE_FORMAT: PHP strftime() date formatting string format for short date representation
 * See http://us.php.net/strftime for format string options
 */
$CONF['SHORT_DATE_FORMAT'] = "%m/%d/%Y";
/**
 * LONG_DATE_FORMAT: PHP strftime() date formatting string format for long date representation
 * See http://us.php.net/strftime for format string options
 */
$CONF['LONG_DATE_FORMAT'] = "%a %h %d, %Y";
/**
 * SKIN: Site style skin, a directory in skins/
 */
$CONF['SKIN'] = "rpc";
?>
