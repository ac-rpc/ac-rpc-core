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
 * Class RPC_Config
 *
 * Application configuration object singleton
 * Reads configuration from config file inc/config.inc.php
 * and stores as object.  Afterward the vars used from config.inc.php
 * are expunged from $GLOBALS[]
 *
 * Some values are created dynamically in the constructor
 *
 * @package RPC
 */

require_once('rpc_version.inc.php');
class RPC_Config
{
	const ERR_INVALID_CONFIG = 1;
	const ERR_MISSING_CONFIG = 2;
	const ERR_NO_AUTH_MODULE = 3;

	/********** Database Configuration ***********/
	/**
	 * MySQL database hostname or IP
	 *
	 * @var string
	 * @access public
	 */
	public $db_host = 'localhost';
	/**
	 * MySQL database port
	 *
	 * @var integer
	 * @access public
	 */
	public $db_port = 3306;
	/**
	 * MySQL database name
	 *
	 * @var string
	 * @access public
	 */
	public $db_name;
	/**
	 * MySQL database user
	 *
	 * @var string
	 * @access public
	 */
	public $db_user;
	/**
	 * MySQL database password
	 *
	 * @var string
	 * @access public
	 */
	public $db_pass = '';


	/********** Authentication Options ***********/
	/**
	 * Array of designated system superusers' usernames
	 *
	 * @var string
	 * @access public
	 */
	public $auth_superusers = array();
	/**
	 * Authentication plugin to use for this session, located in plugins/auth
	 *
	 * @var string
	 * @access public
	 */
	public $auth_plugin = NULL;
	/**
	 * Array of Shibboleth Native SP configuration options
	 *
	 * @var array
	 * @access public
	 */
	public $auth_shib = NULL;


	/********** Session Options ***********/
	/**
	 * PHP session name
	 *
	 * @var string
	 * @access public
	 */
	public $session_name = NULL;

	/********** Path and Library Configuration ***********/
	/**
	 * Fixed filesystem path to the application
	 *
	 * @var string
	 * @access public
	 */
	public $app_file_path;
	/**
	 * HTTP path to application, relative to server document root
	 *
	 * @var string
	 * @access public
	 */
	public $app_relative_web_path;
	/**
	 * Complete HTTP path to application, including protocol and port
	 *
	 * @var string
	 * @access public
	 */
	public $app_fixed_web_path;
	/**
	 * Complete HTTP protocol://hostname:port for use in CLI
	 * scripts.
	 * USE ONLY IN CLI SCRIPTS!
	 * Do not use in regular web scripts, as app_fixed_web_path
	 * is preferred since it is dynamically generated.
	 *
	 * @var string
	 * @access public
	 */
	public $app_http_host;
	/**
	 * Site style skin name (default 'rpc')
	 *
	 * @var string
	 * @access public
	 */
	public $skin;
	/**
	 * Relative path to current application skin
	 *
	 * @var string
	 * @access public
	 */
	public $app_skin_path;
	/**
	 * Path to the dojo toolkit root, relative to server document root
	 *
	 * @var string
	 * @access public
	 */
	public $app_dojo_path;
	/**
	 * Is Apache URL rewriting active?
	 *
	 * @var boolean
	 * @access public
	 */
	public $app_use_url_rewrite;

	/********** Application Configuration ***********/
	/**
	 * Long display title of this application implementation
	 *
	 * @var string
	 * @access public
	 */
	public $app_long_name = 'Research Project Calculator';
	/**
	 * Short/abbreviate title of this application implementation
	 *
	 * @var string
	 * @access public
	 */
	public $app_short_name = 'RPC';
	/**
	 * Emails notifications sent come from this address
	 *
	 * @var string
	 * @access public
	 */
	public $app_email_from_address;
	/**
	 * Emails notifications sent come from this sender name
	 *
	 * @var string
	 * @access public
	 */
	public $app_email_from_sender_name = '';
	/**
	 * RFC complete email address "Sender Name <addr@examaple.com>"
	 * Created by constructor
	 *
	 * @var string
	 * @access public
	 */
	public $app_rfc_email_address = '';
	/**
	 * Date format string in strftime() format, for brief date displays
	 *
	 * @var string
	 * @access public
	 */
	public $short_date_format;
	/**
	 * Date format string in strftime() format, for long date displays
	 *
	 * @var string
	 * @access public
	 */
	public $long_date_format;

	/**
	 * @var object RPC_Config global singleton instance
	 * @static
	 * @access private
	 */
	private static $_instance = NULL;
	/**
	 * Error status
	 *
	 * @var integer
	 * @access public
	 */
	public $error = NULL;

	/**
	 * Constructor reads config.inc.php for $CONF array,
	 * loads into local object, initializes all other config
	 * values then discards $GLOBALS['CONF']
	 *
	 * @param string $filename Alternate config filename
	 * @access private
	 * @return object RPC_Config
	 */
	private function __construct($filename = NULL)
	{
		// Initialize config array before reading its values...
		if (!isset($GLOBALS['CONF']))
		{
			$GLOBALS['CONF'] = array();
		}
		global $CONF;
		// Load file if specified, otherwise default
		if (!empty($filename))
		{
			require_once(realpath($filename));
		}
		else
		{
			require_once(__DIR__ . '/config.inc.php');
		}

		// Config file was found, but had nothing useful
		if (sizeof($GLOBALS['CONF']) == 0)
		{
			$this->error = self::ERR_MISSING_CONFIG;
			$err = "Fatal error: The configuration file inc/config.inc.php could not be loaded.";
			echo $err;
			error_log($err);
			exit();
		}
		// The following keys are REQUIRED in config.inc.php
		// Anyone found missing or empty results in a fatal error
		$required_keys = array(
			'DB_HOST',
			'DB_NAME',
			'DB_USER',
			'AUTH_SUPERUSERS',
			'AUTH_PLUGIN',
			'RELATIVE_WEB_PATH',
			'DOJO_PATH',
			'APP_LONG_NAME',
			'EMAIL_FROM_ADDRESS'
		);
		// Verify that Shibboleth requirements met if shibboleth enabled
		if ($GLOBALS['CONF']['AUTH_PLUGIN'] == 'shibboleth')
		{
			$required_keys[] = 'SHIB_ENTITY_IDENTIFIER';
			$required_keys[] = 'SHIB_USERNAME_KEY';
			$required_keys[] = 'SHIB_EMAIL_KEY';
		}
		$missing_keys = array();
		foreach ($required_keys as $key)
		{
			if (!array_key_exists($key, $GLOBALS['CONF']) || (array_key_exists($key, $GLOBALS['CONF']) && empty($GLOBALS['CONF'][$key])))
			{
				$missing_keys[] = $key;
			}
		}
		// The following keys are ALLOWED in config.inc.php
		// Any unknown key results in a fatal error
		$allowed_keys = array(
			'DB_HOST',
			'DB_PORT',
			'DB_NAME',
			'DB_USER',
			'DB_PASS',
			'AUTH_SUPERUSERS',
			'AUTH_PLUGIN',
			'SHIB_ENTITY_IDENTIFIER',
			'SHIB_USERNAME_KEY',
			'SHIB_EMAIL_KEY',
			'SHIB_MODE',
			'ENTITY_IDENTIFIER',
			'USERNAME_KEY',
			'EMAIL_KEY',
			'ALLOW_GUEST',
			'SESSION_NAME',
			'HTTP_HOST',
			'RELATIVE_WEB_PATH',
			'DOJO_PATH',
			'USE_URL_REWRITE',
			'APP_LONG_NAME',
			'APP_SHORT_NAME',
			'EMAIL_FROM_ADDRESS',
			'EMAIL_FROM_SENDER_NAME',
			'NOTIFICATION_ADVANCE_DAYS',
			'SHORT_DATE_FORMAT',
			'LONG_DATE_FORMAT',
			'SKIN'
		);
		$unknown_keys = array();
		foreach ($GLOBALS['CONF'] as $key=>$val)
		{
			if (!in_array($key, $allowed_keys))
			{
				$unknown_keys[] = $key;
			}
		}
		// Exit with error if any keys are unconfigured
		if (sizeof($missing_keys) > 0 || sizeof($unknown_keys) > 0)
		{
			$this->error = self::ERR_INVALID_CONFIG;
			$err = "Fatal error: The following configuration directives are missing or empty in inc/config.inc.php: " . implode(", ", $missing_keys) . "\n ";
			$err .= "Fatal error: The following configuration directives are unknown or invalid: " . implode(", ", $unknown_keys);
			echo nl2br($err);
			error_log($err);
			exit();
		}
		// Verify the chosen authentication plugin module actually exists
		if (!file_exists(dirname(__FILE__) . '/../plugins/auth/' . $GLOBALS['CONF']['AUTH_PLUGIN'] . '/' . $GLOBALS['CONF']['AUTH_PLUGIN'] . '.inc.php'))
		{
			$this->error = self::ERR_NO_AUTH_MODULE;
			$err = "Fatal error: File plugins/auth/" . $GLOBALS['CONF']['AUTH_PLUGIN'] . "/" .$GLOBALS['CONF']['AUTH_PLUGIN'] . ".inc.php does not exist. The specified authentication module could not be loaded.";
			echo $err;
			error_log($err);
			exit();
		}
		// All well so far, load this object
		$this->db_host = trim($GLOBALS['CONF']['DB_HOST']);
		$this->db_name = trim($GLOBALS['CONF']['DB_NAME']);
		$this->db_user = trim($GLOBALS['CONF']['DB_USER']);
		$this->db_pass = isset($GLOBALS['CONF']['DB_PASS']) ? $GLOBALS['CONF']['DB_PASS'] : "";
		$this->db_port = isset($GLOBALS['CONF']['DB_PORT']) ? $GLOBALS['CONF']['DB_PORT'] : $this->db_port;

		$this->auth_plugin = trim($GLOBALS['CONF']['AUTH_PLUGIN']);

		if ($this->auth_plugin == 'shibboleth') {
			// Shibboleth authorization values
			$this->auth_shib['SHIB_ENTITY_IDENTIFIER'] = trim($GLOBALS['CONF']['SHIB_ENTITY_IDENTIFIER']);
			$this->auth_shib['SHIB_USERNAME_KEY'] = trim($GLOBALS['CONF']['SHIB_USERNAME_KEY']);
			$this->auth_shib['SHIB_EMAIL_KEY'] = trim($GLOBALS['CONF']['SHIB_EMAIL_KEY']);
			$this->auth_shib['SHIB_MODE'] = isset($GLOBALS['CONF']['SHIB_MODE']) ? trim($GLOBALS['CONF']['SHIB_MODE']) : 'passive';

			// Default shibboleth mode to 'passive' if invalid
			if (!in_array($this->auth_shib['SHIB_MODE'], array('active', 'passive'))) {
				$this->auth_shib['SHIB_MODE'] = 'passive';
			}
		}

		$this->session_name = isset($GLOBALS['CONF']['SESSION_NAME']) && !empty($GLOBALS['CONF']['SESSION_NAME']) ? trim($GLOBALS['CONF']['SESSION_NAME']) : 'RPC';

		$this->app_dojo_path = rtrim($GLOBALS['CONF']['DOJO_PATH'], '/') . '/';
		$this->app_use_url_rewrite = isset($GLOBALS['CONF']['USE_URL_REWRITE']) ? $GLOBALS['CONF']['USE_URL_REWRITE'] : FALSE;

		// Relative web path
		$this->app_relative_web_path = $GLOBALS['CONF']['RELATIVE_WEB_PATH'] == '/' ? '/' : rtrim($GLOBALS['CONF']['RELATIVE_WEB_PATH'], "/") . '/';

		// HTTP host spec for CLI scripts
		$this->app_http_host = isset($GLOBALS['CONF']['HTTP_HOST']) && !empty($GLOBALS['CONF']['HTTP_HOST']) ? trim($GLOBALS['CONF']['HTTP_HOST']) : "http://" . $_ENV['HOSTNAME'];

		$this->app_long_name = trim($GLOBALS['CONF']['APP_LONG_NAME']);
		// Short name defaults to long name if not specified
		$this->app_short_name = isset($GLOBALS['CONF']['APP_SHORT_NAME']) ? trim($GLOBALS['CONF']['APP_SHORT_NAME']) : $this->app_long_name;
		$this->app_email_from_address = trim($GLOBALS['CONF']['EMAIL_FROM_ADDRESS']);
		$this->app_email_from_sender_name = isset($GLOBALS['CONF']['EMAIL_FROM_SENDER_NAME']) ? trim($GLOBALS['CONF']['EMAIL_FROM_SENDER_NAME']) : "";
		$this->app_notification_advance_days = isset($GLOBALS['CONF']['NOTIFICATION_ADVANCE_DAYS']) ? intval($GLOBALS['CONF']['NOTIFICATION_ADVANCE_DAYS']) : 2;

		// Date formats
		// Short date defaults to "%m-%d-%Y" (mm-dd-yyyy)
		$this->short_date_format = isset($GLOBALS['CONF']['SHORT_DATE_FORMAT']) ? $GLOBALS['CONF']['SHORT_DATE_FORMAT'] : "%m-%d-%Y";
		// Long date defaults to "%a %h %d, %Y" (WkDay Mon dd, yyyy)
		$this->long_date_format = isset($GLOBALS['CONF']['LONG_DATE_FORMAT']) ? $GLOBALS['CONF']['LONG_DATE_FORMAT'] : "%a %h %d, %Y";

		// Load array of superusers
		$this->auth_superusers = preg_split('/[\s,]+/', $GLOBALS['CONF']['AUTH_SUPERUSERS']);

		// Calculated members
		// RFC address (Full Name <address@example.com>) defaults to address only if name is not specified
		$this->app_rfc_email_address = !empty($this->app_email_from_sender_name) ? "{$this->app_email_from_sender_name} <{$this->app_email_from_address}>" : $this->app_email_from_address;
		// No line breaks allowed in the rfc2822 address
		$this->app_rfc_email_address = preg_replace("/\r?\n/", " ", $this->app_rfc_email_address);


		// Static file path determined from the cwd
		$this->app_file_path = substr(dirname(__FILE__),0,strpos(dirname(__FILE__),DIRECTORY_SEPARATOR . 'inc'));

		// Full web path from relative path and protocol
		// Generate dynamically if this is a web session, or use $this->app_http_host if this is a CLI session
		if (isset($_SERVER['SERVER_NAME']))
		{
			$this->app_fixed_web_path = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? "https://" : "http://";
			$this->app_fixed_web_path .= $_SERVER['SERVER_NAME'];
			// Append port if not 80/443
			$this->app_fixed_web_path .= $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443 ? ":" . $_SERVER['SERVER_PORT'] : "";
			$this->app_fixed_web_path .= $this->app_relative_web_path;
		}
		else
		{
			$this->app_fixed_web_path = $this->app_http_host . $this->app_relative_web_path;
		}

		// Skin location
		if (isset($GLOBALS['CONF']['SKIN']) && file_exists($this->app_file_path . '/skins/' . trim($GLOBALS['CONF']['SKIN'])))
		{
			$this->skin = $GLOBALS['CONF']['SKIN'];
		}
		else $this->skin = 'rpc';
		$this->app_skin_path = $this->app_relative_web_path . 'skins/' . $this->skin . '/';

		// From here on, we have no further need for the global array $CONF[]
		unset($GLOBALS['CONF']);

		return;
	}
	// Can't clone singleton
	private function __clone() {}

	/**
	 * Singleton accessor method
	 *
	 * @param string $filename Alternate config filename
	 * @access public
	 * @return object RPC_Config Global configuration singleton
	 */
	public static function get_instance($filename = NULL)
	{
		if (self::$_instance == NULL)
		{
			self::$_instance = new self($filename);
		}
		return self::$_instance;
	}
}
?>
