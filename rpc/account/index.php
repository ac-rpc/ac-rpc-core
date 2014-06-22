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
 * Process logins from the native authentication plugin
 */
require_once(__DIR__ . '/../../vendor/autoload.php');

$config = RPC_Config::get_instance();
$db = RPC_DB::get_connection($config);
// Guests can perform some account actions.
RPC::init(RPC_User::RPC_AUTHLEVEL_GUEST, $config, $db);

$smarty = new RPC_Smarty($config);

// Default action is modify account settings
$account_action = isset($_GET['acct']) ? $_GET['acct'] : 'modify';
switch ($account_action)
{
	/************************ RESET PASSWORD ******************************/
	case 'resetpw':
		// Native authentication only
		if ($config->auth_plugin == 'native')
		{
			$smarty->assign('acct_action', 'resetpw');
			$smarty->assign('acct_handler', $config->app_fixed_web_path . "account/?acct=resetpw");
			if (isset($_POST['username']) && isset($_POST['transid']))
			{
				// Verify form isn't being resubmitted.  $_SESSION['transid'] won't be set if resubmitted
				if (isset($_SESSION['transid']) && $_SESSION['transid'] == $_POST['transid'])
				{
					$reset_user = new Native_User($_POST['username'], $config, $db);
					if ($reset_user->error == Native_User::ERR_NO_SUCH_USER)
					{
						$smarty->assign('acct_error', "No account could be located for " . htmlentities($_POST['username']));
						$smarty->assign('transid', $_SESSION['transid']);
					}
					else if (!empty($reset_user->error))
					{
						// This shouldn't happen -- database error
						$err = "A database error occurred.";
						error_log($err . ' (' . $db->errno . ') ' . $db->error);
						$smarty->assign('acct_error', $err);
					}
					// Go ahead and process the reset
					else
					{
						$reset_user->recover_password();
						unset($_SESSION['transid']);
						$smarty->assign('acct_success', 'A new password has been emailed to ' . htmlentities($_POST['username']) . " (<a href='{$config->app_relative_web_path}?acct=login'>Login</a>)");
					}
				}
			}
			// Otherwise, show the form
			else
			{
				$_SESSION['transid'] = md5(time() . rand());
				$smarty->assign('transid', $_SESSION['transid']);
			}
		}
		// No caching for form displays.
		header('Cache-control: private');
		header('Pragma: no-cache');
		$smarty->skin_display('forms/native_resetpw.tpl');
		exit();
		break;

	/************************  NEW ACCOUNT CREATION *****************************/
	case 'newacct':
		// Native authentication only
		if ($config->auth_plugin == 'native')
		{
			$smarty->assign('acct_action', 'newacct');
			$smarty->assign('acct_handler', $config->app_fixed_web_path . "account/?acct=newacct");
			// Check if this was a form submission
			if (isset($_POST['username']) && isset($_POST['transid']))
			{
				// Verify form isn't being resubmitted.  $_SESSION['transid'] won't be set if resubmitted
				if (isset($_SESSION['transid']) && $_SESSION['transid'] == $_POST['transid'])
				{
					// Validate required email, password, usertype
					$arr_resubmit = array();
					// Username is valid email address
					if (!RPC_User::validate_email($_POST['username'])) $arr_resubmit['username'] = TRUE;
					// username OK, pass back into smarty
					else $smarty->assign('createacct_username', $_POST['username']);
					// Password meets minimum complexity, and confirmation must match...
					if (!Native_User::password_meets_complexity($_POST['password']) || ($_POST['password'] !== $_POST['password-confirm']))
					{
						$arr_resubmit['password'] = TRUE;
					}
					// Password never gets passed back into smarty!
					// Usertype required as STUDENT or TEACHER
					if (!isset($_POST['usertype']) || (isset($_POST['usertype']) && $_POST['usertype'] !== 'TEACHER' && $_POST['usertype'] !== 'STUDENT'))
					{
						$arr_resubmit['usertype'] = TRUE;
					}
					else $smarty->assign('createacct_usertype', $_POST['usertype']);

					// Name isn't required, so just pass back to smarty
					if (!empty($_POST['name']))
					{
						$smarty->assign('createacct_name', $_POST['name']);
					}

					// Any invalid fields, go back to the form, hilighting invalid fields
					if (sizeof($arr_resubmit) > 0)
					{
						$smarty->assign('acct_error', 'Some required fields were omitted or invalid.');
						$smarty->assign('resubmit', $arr_resubmit);
						$smarty->assign('transid', $_SESSION['transid']);
					}
					// Nothing invalid, so go ahead and create the user
					else
					{
						// Does the requested user account already exist?
						$create_user_exists = RPC_User::username_exists($_POST['username'], $db);
						switch ($create_user_exists)
						{
							// User already exists, show form to create a different account
							case RPC_User::RPC_EXISTS:
								$smarty->assign('acct_error', "User {$_POST['username']} already exists!");
								$smarty->assign('transid', $_SESSION['transid']);
								break;
							// Some error, probably database error
							case RPC_User::RPC_EXISTS_ERROR:
								$err = 'Error: User could not be created. A database error occurred.';
								error_log($err . ' (' . $db->errno . ') '. $db->error);
								$smarty->assign('acct_error', $err);
								$smarty->assign('transid', $_SESSION['transid']);
								break;
							// User doesn't already exist. Create it.
							case RPC_User::RPC_NOT_EXISTS:
								$create_username = trim($_POST['username']);
								$create_name = isset($_POST['name']) && trim($_POST['name']) != '' ? trim($_POST['name']) : "";
								// Always create as regular user
								$create_perms = RPC_User::RPC_AUTHLEVEL_USER;
								$create_type = $_POST['usertype'];
								$create_user_success = RPC_User::create_user($create_username, $create_name, $create_username, $create_type, $create_perms, $db);
								if ($create_user_success)
								{
									// Build user object and set password
									$created_user = new Native_User($create_username, $config, $db);
									if (empty($user->error))
									{
										// Old password is empty string, since none has yet been set
										$created_user->set_password("", $_POST['password']);

										// Login the new user
										$created_user->set_authenticated();
										// And load into smarty
										$smarty->set_user($created_user);
									}
									$db->commit();
									unset($_SESSION['transid']);
									$smarty->assign('acct_success', "User $create_username was successfully created. <a href='{$config->app_relative_web_path}'>Go to the home page</a>");
								}
								else
								{
									$err = 'Error: User could not be created. A database error occurred.';
									error_log($err . ' (' . $db->errno . ') '. $db->error);
									$smarty->assign('acct_error', $err);
									$smarty->assign('transid', $_SESSION['transid']);
								}
								break;
						}
					}
				}
			}
			else
			{
				// Wasn't a form submission, so display the form.
				$_SESSION['transid'] = md5(time() . rand());
				$smarty->assign('transid', $_SESSION['transid']);
				$smarty->assign('acct_handler', $config->app_fixed_web_path . "account/?acct=newacct");
			}

			// Display the account creation template
			header('Cache-control: private');
			header('Pragma: no-cache');
			$smarty->skin_display('forms/native_newacct.tpl');
			exit();
		}
		break;

	/************************ LOGOUT USER *****************************/
	case 'logout':
		// Don't force a login if user isn't logged in
		$user = RPC::get_active_user();
		$acct_login_string = "<a href='{$config->app_relative_web_path}?acct=login'>Login</a>";
		if ($user)
		{
			$smarty->assign('user', NULL);
			$user = NULL;
			RPC::destroy_active_user();
			$smarty->assign('acct_success', "You have been logged out. ($acct_login_string)");
		}
		else
		{
			$smarty->assign('acct_success', "You are not logged in. ($acct_login_string)");
		}
		$smarty->assign('acct_action', 'logout');
		$smarty->skin_display('forms/native_modifyacct.tpl');
		break;
	/************************ MODIFY EXISTING ACCOUNT DETAILS *****************************/
	case 'modify':
	default:
		// Must have a logged-in user
		$user = RPC::get_active_user();
		if ($user)
		{
			$smarty->set_user($user);
			$smarty->assign('acct_action', 'modify');
			$smarty->assign('acct_handler', $config->app_fixed_web_path . "account/?acct=modify");
			// Is a form post, so process it
			if (isset($_POST['email']) && (isset($_SESSION['transid']) && $_POST['transid'] === $_SESSION['transid']))
			{
				$arr_resubmit = array();
				// Start with success flags for all changes.
				// If changes are made, they may be overwritten with failures...
				$modify_email_success = TRUE;
				$modify_type_success = TRUE;
				$modify_name_success = TRUE;
				$acct_error = "";
				// If changing email address, verify the new address isn't already in use
				// For native auth users, changing email also changes username.  For API auth
				// users, only the email address will change.  Either way, it can't be in use already
				if ($_POST['email'] !== $user->email)
				{
					if (!RPC_User::validate_email($_POST['email']))
					{
						$arr_resubmit['email'] = TRUE;
						$modify_email_success = FALSE;
						$acct_error = "\n<br />The supplied email address was invalid.";
					}
					else if (RPC_User::email_exists($_POST['email'], $db))
					{
						$arr_resubmit['email'] = TRUE;
						$modify_email_success = FALSE;
						$acct_error = "\n<br />The requested email address is already in use.";
					}
					// New email is usable, so update it
					else
					{
						$modify_email_success = $user->set_email($_POST['email']);
						if (!$modify_email_success) $arr_resubmit['name'] = TRUE;
					}
				}
				// Type changing
				if ($_POST['usertype'] !== $user->type)
				{
					$modify_type_success = $user->set_type($_POST['usertype']);
					if (!$modify_type_success) $arr_resubmit['usertype'] = TRUE;
				}
				// Name is changing...
				if ($_POST['name'] !== $user->name)
				{
					$modify_name_success = $user->set_name($_POST['name']);
					if (!$modify_name_success) $arr_resubmit['name'] = TRUE;
				}
				// Verify all actions were successful, and commit
				if ($modify_email_success && $modify_type_success && $modify_name_success)
				{
					$db->commit();
					unset($_SESSION['transid']);
					$smarty->assign('acct_success', "Your account settings were successfully changed. (<a href='{$config->app_fixed_web_path}'>Return to the home page</a>)");
					// For native authentication, and possibly others, the local username has now changed.
					// $_SESSION needs to be updated to reflect this
					$_SESSION['username'] = $user->username;
					$_SESSION['transid'] = md5(time() . rand());
					$smarty->assign('transid', $_SESSION['transid']);
					// Reload the user object into smarty to reflect new changes
					$smarty->set_user($user);
				}
				else
				{
					$db->rollback();
					$smarty->assign('transid', $_SESSION['transid']);
					$smarty->assign('resubmit', $arr_resubmit);
					$smarty->assign('acct_error', "Some fields could not be modified." . $acct_error);
				}
			}
	      /************************ CHANGE PASSWORD (NATIVE) *****************************/
			// Native authentication users may be changing passwords...
			else if (isset($_POST['oldpassword']) && (isset($_SESSION['transid']) && $_POST['transid'] === $_SESSION['transid']))
			{
				$arr_resubmit = array();
				// Passwords must match and be minimum 6 characters
				if ($_POST['password'] === $_POST['password-confirm'])
				{
					if ($user->set_password($_POST['oldpassword'], $_POST['password']))
					{
						$db->commit();
						$_SESSION['transid'] = md5(time() . rand());
						$smarty->assign('transid', $_SESSION['transid']);
						$smarty->assign('acct_success', "Your password has been changed.");
					}
					else
					{
						if ($user->error == Native_User::ERR_INCORRECT_CREDS)
						{
							$arr_resubmit['oldpassword'] = TRUE;
							$smarty->assign('resubmit', $arr_resubmit);
							$smarty->assign('transid', $_SESSION['transid']);
							$smarty->assign('acct_error', "You entered your old password incorrectly.");
						}
						else if ($user->error == Native_User::ERR_PASWORD_COMPLEXITY_UNMET)
						{
							$arr_resubmit['password'] = TRUE;
							$smarty->assign('resubmit', $arr_resubmit);
							$smarty->assign('transid', $_SESSION['transid']);
							$smarty->assign('acct_error', "New password must be at least 6 characters and contain at least one number.");
						}
						else
						{
							$db->rollback();
							$smarty->assign('transid', $_SESSION['transid']);
							$smarty->assign('resubmit', $arr_resubmit);
							$smarty->assign('acct_error', "A database error occurred.");
						}
					}
				}
				else
				{
					$arr_resubmit['password'] = TRUE;
					$smarty->assign('transid', $_SESSION['transid']);
					$smarty->assign('acct_error', "Your passwords did not match.");
				}
			}
			/************************ DELTE ACCOUNT *****************************/
			else if (isset($_POST['delete-confirm']) && isset($_SESSION['transid']) && $_POST['transid'] === $_SESSION['transid'])
			{
				if ($user->delete_account())
				{
					$db->commit();
					// Remove the user from smarty
					$smarty->assign('user', NULL);
					// Kill the local $user
					$user = NULL;
					// And kill the global user singleton
					RPC::destroy_active_user();
					$smarty->assign('acct_success', "Your account has been deleted. <a href='{$config->app_relative_web_path}'>Go to the home page</a>");
				}
				else
				{
					$db->rollback();
					$smarty->assign('transid', $_SESSION['transid']);
					$smarty->assign('acct_error', 'A database error occurred.  Your account could not be deleted.');
				}
			}
			// Otherwise setup the basic form
			else
			{
				$_SESSION['transid'] = md5(time() . rand());
				$smarty->assign('transid', $_SESSION['transid']);
			}
			// Display the account creation template
			header('Cache-control: private');
			header('Pragma: no-cache');
			$smarty->skin_display('forms/native_modifyacct.tpl');
			break;
		}
		// Failed user login: go to index.php
		else
		{
			header("Location: " . $config->app_fixed_web_path . "?acct=login");
			exit();
		}
}
?>
