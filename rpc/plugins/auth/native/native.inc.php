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
 * RPC Native authentication plugin
 * Stores local users with passwords, using email address as username
 *
 * @package RPC
 */
/**
 * Mandatory function for plugin authentication
 * Returns pipe-delimited authentication string in the format:
 *		(OK/FAIL)|username|perms|fail-reason
 * 
 * @param boolean $enforce Should login be enforced in this function call?
 *						When $enforce is TRUE, user will be forced to login, following
 *						any necessary redirects.
 *						When $enforce is FALSE, cookies will be checked for active logins,
 *						and the user created if they are found. If not found, the user will not be
 *						created.
 * @param object $config Global RPC_Config configuration singleton (optional)
 * @param object $db MySQLi database connection singleton (optional)
 * @access public
 * @return object Native_User valid RPC user
 */
function rpc_authenticate($enforce=TRUE, $config=NULL, $db=NULL)
{
	// Initialize parts
	$auth_username = "";

	// First check if this was an authentication post request and process accordingly.
	if (isset($_POST['username']) && (isset($_POST['transid']) && isset($_SESSION['transid'])) && ($_POST['transid'] == $_SESSION['transid']))
	{
		// See if the requested user exists
		if (Native_User::username_exists(trim($_POST['username']), $db))
		{
			// Build a user object to authenticate
			$user = new Native_User($_POST['username'], $config, $db);
			if (empty($user->error))
			{
				// Persistent login?
				$persist = isset($_POST['persist']) && $_POST['persist'] == 'on' ? TRUE : FALSE;
				$valid = $user->validate_password($_POST['password'], $persist);
				if ($valid)
				{
					$auth_username = $user->username;
					$_SESSION['username'] = $user->username;
				}
				// Failed password.  Back to login screen and exit
				else
				{
					$smarty = native_smarty_prepare_login($config);
					$smarty->assign('login_error', "Incorrect password. Please try again.");
					$smarty->assign('login_user', $user->username);
					$smarty->skin_display('forms/native_login.tpl');
					exit();
				}
			}
		}
		else
		{
			// The user didn't exist
			$smarty = native_smarty_prepare_login($config);
			$smarty->assign('login_error', "The requested email address was not found");
			$smarty->skin_display('forms/native_login.tpl');
			exit();
		}
	}
	// Didn't attempt to process a form login, so check session and cookie
	if (empty($auth_username))
	{
		// Initialize cookie session ID
		$cookie = Native_User::parse_cookie();
		$session = isset($cookie['session']) ? $cookie['session'] : FALSE;
		// Check $_SESSION, then $_COOKIE to get a username
		if (isset($_SESSION['username'])) $auth_username = $_SESSION['username'];
		// If not $_SESSION, try the cookie
		if (empty($auth_username))
		{
			// Validate the cookie, which return username
			$auth_username = Native_User::validate_cookie($db);
		}
		// We have a username, so create the user object
		if (!empty($auth_username) && !isset($user))
		{
			$user = new Native_User($auth_username, $config, $db);
			if (empty($user->error))
			{
				$auth_username = $user->username;
				$user->is_authenticated = TRUE;
				// If a cookie was set before, generate a new one now.
				if (isset($_COOKIE['RPCAUTH']))
				{
					$user->session = $session;
					$user->set_token();
					$user->set_cookie();
				}
			}
		}
		// Okay, no cookie either, so show login screen and exit
		// if logins are being enforced.  Otherwise, all done.
		else
		{
			if ($enforce)
			{
				$smarty = native_smarty_prepare_login($config);
				$smarty->skin_display('forms/native_login.tpl');
				exit();
			}
			// Haven't logged a user in, so return FALSE
			else return FALSE;
		}
	}
	// All done authenticating by form or session/cooke, return the user
	// Native authentication plugin returns a Native_User object, compatible with RPC_User
	// If developing your own authentication plugin, you may either return a complete RPC_User object
	// or the pipe-delimited string.
	return $user;
}

/**
 * Destroy the current login session
 * 
 * @param object $user RPC_User to log out
 * @access public
 * @return void
 */
function rpc_logout($user)
{
	$user->destroy_session();
	$user->unset_cookie();
	return;
}
/**
 * Setup a Smarty template object, wrapping RPC_Smarty::__construct()
 * Creates transaction ID and form action for login handler script
 * 
 * @param object $config Global RPC_Config configuration singleton
 * @access public
 * @return object Smarty
 */
function native_smarty_prepare_login($config)
{
	$smarty = new RPC_Smarty($config);
	$_SESSION['transid'] = md5(time() . rand());
	$smarty->assign('transid', $_SESSION['transid']);
	// Login will 
	$smarty->assign('login_handler', $_SERVER['REQUEST_URI']);
	$smarty->assign('acct_action', 'login');
	return $smarty;
}
?>
