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
 * Class RPC
 * Abstract class for application page/action setup
 *
 * @abstract
 * @package RPC
 */
abstract class RPC
{
	/**
	 * Required authentication level for the currently running script
	 * as defined by RPC_User
	 *
	 * @static
	 * @var integer
	 * @access private
	 */
	private static $_required_authlevel = NULL;

	/**
	 * Currently logged in user, retrieved as a singleton
	 *
	 * @static
	 * @var object RPC_User
	 * @access private
	 */
	private static $_active_user = NULL;

	/**
	 * RPC_Config global configuration singleton
	 *
	 * @static
	 * @var object RPC_Config
	 * @access private
	 */
	private static $_config = NULL;
	/**
	 * MySQLi database connection singleton
	 *
	 * @static
	 * @var object MySQLi database connection
	 * @access private
	 */
	private static $_db = NULL;

	/**
	 * Initialize the application for the currently running script
	 * 1. Sets the minimum authentication level
	 * 2. Starts the PHP session
	 *
	 * @param integer $authlevel Target authentication level for this script
	 * @param object $config RPC_Config global configuration singleton
	 * @param object $db MySQLi global database connection singleton
	 * @static
	 * @access public
	 * @return void
	 */
	public static function init($authlevel, $config, $db)
	{
		$arr_authlevels = array(
			RPC_User::RPC_AUTHLEVEL_GUEST,
			RPC_User::RPC_AUTHLEVEL_USER,
			RPC_User::RPC_AUTHLEVEL_PUBLISHER,
			RPC_User::RPC_AUTHLEVEL_ADMINISTRATOR
		);
		// Invalid authlevel is a fatal error
		if (!in_array($authlevel, $arr_authlevels))
		{
			$err = "Fatal error: Invalid \$authlevel was passed to RPC::init().";
			echo $err;
			error_log($_SERVER['SCRIPT_NAME'] . ": " . $err);
			exit();
		}
		else self::$_required_authlevel = $authlevel;

		self::$_config = $config;
		self::$_db = $db;

		// Start the PHP session...
		session_name(self::$_config->session_name);
		session_start();
		return;
	}

	/**
	 * Close resources
	 *
	 * @static
	 * @access public
	 * @return void
	 */
	public static function cleanup()
	{
		self::$_required_authlevel = NULL;
		self::$_active_user = NULL;
		self::$_config = NULL;
		self::$_db->close();
		return;
	}

	/**
	 * Retrieve the currently logged in user,
	 * or if no user has yet logged in, launch the auth_plugin
	 * and sign in a user.
	 *
	 * @static
	 * @access public
	 * @return object RPC_User or FALSE if no user is logged in
	 */
	public static function get_active_user()
	{
		// If the authlevel hasn't been set yet, this cannot continue.
		// Exit with fatal error
		if (self::$_required_authlevel === NULL)
		{
			$err = "Fatal error: Page required authlevel is not set. RPC::init() must be called before RPC::get_active_user().";
			echo $err;
			error_log($_SERVER['SCRIPT_NAME'] . ": " . $err);
			exit();
		}

		// Don't have a user yet, so sign one in via auth_plugin
		if (self::$_active_user == NULL)
		{
			// First, store $_POST in $_SESSION if it is set, so we can recover the $_POST data after redirects
			if (sizeof($_POST) > 0) $_SESSION['last_post'] = $_POST;

			// Load the auth_plugin
			require_once(self::$_config->app_file_path . '/plugins/auth/' . self::$_config->auth_plugin . '/' . self::$_config->auth_plugin . '.inc.php');
			// Main authentication method to retrieve either the plugin auth string or an RPC_User object
			// Enforce a login?  If guests are allowed now, FALSE, else TRUE
			$enforce = self::$_required_authlevel > RPC_User::RPC_AUTHLEVEL_GUEST ? TRUE : FALSE;
			$auth_user = rpc_authenticate($enforce, self::$_config, self::$_db);

			if ($auth_user !== FALSE)
			{
				// Login is done, and all redirects should be completed, so safe to get $_POST back out of $_SESSION
				if (isset($_SESSION['last_post']))
				{
					$_POST = $_SESSION['last_post'];
					unset($_SESSION['last_post']);
				}

				// If rpc_authenticate() returned a string, parse it and create a local user
				if (is_string($auth_user))
				{
					$arr_auth_user = preg_split("/\|/", $auth_user);
					// Invalid return string is a fatal error
					if (!sizeof($arr_auth_user) == 5)
					{
						$err = "Fatal error: Authentication string is not in the format '(OK/FAIL)|username|email|permissions|fail-reason'.";
						echo $err;
						error_log($_SERVER['SCRIPT_NAME'] . ": " . $err);
						exit();
					}
					// Does the user exist locally?  If not, create.
					if (!RPC_User::username_exists($arr_auth_user[1], self::$_db))
					{
						// Permissions, if omitted from validation string will be RPC_AUTHLEVEL_USER
						$create_email = !empty($arr_auth_user[2]) && RPC_User::validate_email($arr_auth_user[2]) ? $arr_auth_user[2] : "";
						$create_perms = !empty($arr_auth_user[3]) ? $arr_auth_user[3] : RPC_User::RPC_AUTHLEVEL_USER;
						$create_success = RPC_User::create($arr_auth_user[1], '', $create_email, 'STUDENT', $create_perms, self::$_db);
						if ($create_success)
						{
							self::$_db->commit();
						}
						else
						{
							// Fatal error creating user
							$err = "Fatal error: Local user could not be created.";
							echo $err;
							error_log($_SERVER['SCRIPT_NAME'] . ": " . $err);
							exit();
						}
					}
					// Build the logged-in user
					self::$_active_user = new RPC_User(RPC_User::RPC_QUERY_USER_BY_USERNAME, $arr_auth_user[1], self::$_config, self::$_db);
				}
				// An object was returned
				if (is_object($auth_user))
				{
					// Setup the logged-in user, assume it was returned correctly by rpc_authenticate()
					self::$_active_user = $auth_user;
				}

				// Check page permissions.  If $_active_user doesn't meet $_required_authlevel, it will be
				// returned with error status
				if (self::$_active_user->raw_perms_int < self::$_required_authlevel)
				{
					self::$_active_user->error = RPC_User::ERR_ACCESS_DENIED;
				}

				// Set the username in $_SESSION
				$_SESSION['username'] = self::$_active_user->username;
				return self::$_active_user;
			}
			// No user was authenticated by rpc_authenticate(), possibly
			// because $enforce was FALSE
			else return FALSE;
		}
		// Active user is already setup
		else
		{
			// Set the username in $_SESSION
			$_SESSION['username'] = self::$_active_user->username;
			return self::$_active_user;
		}
	}
	/**
	 * Unset the current _active_user and remove the session cookie
	 *
	 * @static
	 * @access public
	 * @return void
	 */
	public static function destroy_active_user()
	{
		// First call the auth plugin's rpc_logout()
		// if it has been prototyped.
		if (function_exists('rpc_logout'))
		{
			rpc_logout(self::$_active_user);
		}
		$_SESSION = array();
		self::$_active_user = NULL;
		session_destroy();
		setcookie(self::$_config->session_name, '', time() - 86400);
		return;
	}

	/**
	 * Return the required authentication level for this script
	 * Levels defined by RPC_User
	 *
	 * @static
	 * @access public
	 * @return integer
	 */
	public static function get_required_authlevel()
	{
		return self::$_required_authlevel;
	}
	/**
	 * Store $_POST into a session container to be held across login actions
	 * Retrieve it later with RPC::retrieve_handler_post()
	 *
	 * @static
	 * @access public
	 * @return void
	 */
	public static function store_handler_post()
	{
		// Only store when not already present
		if (!array_key_exists('handler_post', $_SESSION))
		{
			$_SESSION['handler_post'] = $_POST;
		}
		return;
	}
	/**
	 * Retrieve last handler $_POST and delete it from $_SESSION
	 *
	 * @static
	 * @access public
	 * @return void
	 */
	public static function retrieve_handler_post()
	{
		if (array_key_exists('handler_post', $_SESSION))
		{
			$handler_post = $_SESSION['handler_post'];
			unset($_SESSION['handler_post']);
			return $handler_post;
		}
		else return FALSE;
	}
}
?>
