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

require_once(__DIR__ . '/../vendor/autoload.php');

// Application setup
$config = RPC_Config::get_instance();
$db = RPC_DB::get_connection($config);
$smarty = new RPC_Smarty($config);
// Guest is permissible for the index page, but some actions require higher perms.
// If a login was requested, set authlevel as RPC_AUTHLEVEL_USER.  Otherwise RPC_AUTHLEVEL_GUEST.
if (isset($_GET['acct']) && $_GET['acct'] == 'login')
{
	$smarty->assign('login_handler', preg_replace('/[\?&]{1}acct=login/', '', $_SERVER['REQUEST_URI']));
	RPC::init(RPC_User::RPC_AUTHLEVEL_USER, $config, $db);
}
else
{
	RPC::init(RPC_User::RPC_AUTHLEVEL_GUEST, $config, $db);
}

// Store transaction id if one isn't already set
if (!isset($_SESSION['transid']))
{
	$_SESSION['transid'] = md5(time() . rand());
}
$smarty->assign('transid', $_SESSION['transid']);
if (isset($_SESSION['general_success']))
{
	$smarty->assign('general_success', $_SESSION['general_success']);
	unset($_SESSION['general_success']);
}
if (isset($_SESSION['general_error']))
{
	$smarty->assign('general_error', $_SESSION['general_error']);
	unset($_SESSION['general_error']);
}

// Get the user if already logged in, or if enforcing RPC_AUTHLEVEL_USER
$user = RPC::get_active_user();

// Guest user is FALSE but still may be able to view assignments
// So create an empty user
$smarty_user = NULL;
if (!$user)
{
	$user = new RPC_User(RPC_User::RPC_DO_NOT_QUERY, NULL, $config, $db);
	// The unauthenticated user may have checked the teacher box when creating an assignment
	if (isset($_SESSION['active_assignment_usertype']))
	{
		$user->type = $_SESSION['active_assignment_usertype'];
		$smarty->assign('guest_usertype', $_SESSION['active_assignment_usertype']);
	}
}
else $smarty_user = $user;

// User errors get unauthenticated template
if (!empty($user->error))
{
	$smarty->assign('general_error', $user->get_error());
	$view_tpl = 'page/main_guest.tpl';
}
else
{
	$smarty->set_user($smarty_user);
	// If we have an assignment sitting in _SESSION, display it.
	if (isset($_SESSION['active_assignment_object']))
	{
		$active_obj = RPC_Assignment::retrieve_from_session($config, $db);
		$smarty->set_assignment($active_obj);

		// Actions that can be performed on temparary (session) assignments
		if (array_key_exists('action', $_GET) && in_array(strtolower($_GET['action']), array('clear','copy')))
		{
			switch (strtolower($_GET['action']))
			{
				case 'clear': $view_tpl = 'page/assignment_clear.tpl'; break;
				case 'copy': $view_tpl = 'page/assignment_copy.tpl'; break;
			}
		}
		else $view_tpl = 'page/assignment_view.tpl';
	}
	// If an assignment was requested, display it.  Otherwise, list all for user.
	else if
	(
		isset($_GET['assign']) && ctype_digit(strval($_GET['assign']))
		|| isset($_GET['tmpl']) && ctype_digit(strval($_GET['tmpl']))
		|| isset($_GET['link']) && ctype_digit(strval($_GET['link']))
	)
	{
		if (array_key_exists("assign", $_GET))
		{
			$object_type = RPC_Smarty::OBJECT_TYPE_ASSIGNMENT;
			$object_type_name = "assignment";
		}
		else if (array_key_exists("tmpl", $_GET))
		{
			$object_type = RPC_Smarty::OBJECT_TYPE_TEMPLATE;
			$object_type_name = "template";
		}
		else //(array_key_exists("link", $_GET))
		{
			$object_type = RPC_Smarty::OBJECT_TYPE_LINKED_ASSIGNMENT;
			$object_type_name = "link";
		}

		switch ($object_type)
		{
			case RPC_Smarty::OBJECT_TYPE_ASSIGNMENT:
				$active_obj = new RPC_Assignment($_GET['assign'], $user, $config, $db);
				break;
			case RPC_Smarty::OBJECT_TYPE_TEMPLATE:
				$active_obj = new RPC_Template($_GET['tmpl'], $user, $config, $db);
				break;
			case RPC_Smarty::OBJECT_TYPE_LINKED_ASSIGNMENT:
				$active_obj = new RPC_Linked_Assignment($_GET['link'], $user, $config, $db);
				break;
		}
		if (empty($active_obj->error))
		{
			// Smarty must read from linked assignment's $assignment property
			if (get_class($active_obj) === "RPC_Linked_Assignment")
			{
				$smarty->set_assignment($active_obj, $object_type);
			}
			else $smarty->set_assignment($active_obj, $object_type);

			if (array_key_exists('action', $_GET))
			{
				if (in_array(strtolower($_GET['action']), $active_obj->valid_actions))
				{
					switch (strtolower($_GET['action']))
					{
						case 'edit':
							if ($active_obj->is_editable)
							{
								$view_tpl = 'page/assignment_edit.tpl';
							}
							else
							{
								$smarty->assign('general_error', 'You are not allowed to edit this item.');
								$view_tpl = 'page/rpc_access_denied.tpl';
							}
							break;
						case 'delete':
							if ($active_obj->is_editable)
							{
								$view_tpl = 'page/assignment_delete.tpl';
							}
							else
							{
								$smarty->assign('general_error', 'You are not allowed to delete this item.');
								$view_tpl = 'page/rpc_access_denied.tpl';
							}
							break;
						case 'copy':
							$view_tpl = 'page/assignment_copy.tpl';
							break;
						case 'link':
							$view_tpl = 'page/assignment_link.tpl';
							break;
						default: $view_tpl = 'page/assignment_view.tpl'; break;
					}
				}
				else
				{
					$smarty->assign('general_error', 'Invalid action');
					$view_tpl = 'page/rpc_default.tpl';
				}
			}
			// No action parameter -- just display template
			else
			{
				$view_tpl = 'page/assignment_view.tpl';
			}
		}
		else
		{
			$smarty->assign('general_error', $active_obj->get_error());
			switch ($active_obj->error)
			{
				case RPC_Linked_Assignment::ERR_ACCESS_DENIED:
				case RPC_Assignment_Base::ERR_ACCESS_DENIED:
					// No logged in user, show login
					if (!$user->id)
					{
						if ($object_type_name == "assignment") $redir_obj = RPC_Assignment::get_url($active_obj->id, "", $config);
						if ($object_type_name == "link") $redir_obj = RPC_Linked_Assignment::get_url($active_obj->id, "", $config);
						if ($config->app_use_url_rewrite) $redir_append = "?acct=login";
						else $redir_append = "&acct=login";
						header("Location: {$redir_obj}{$redir_append}");
						exit();
					}
					// Logged in user isn't allowed, show access denied page.
					else $view_tpl = 'page/rpc_access_denied.tpl';
					break;
				case RPC_Linked_Assignment::ERR_NO_SUCH_OBJECT:
				case RPC_Assignment_Base::ERR_NO_SUCH_OBJECT:
				default:
					$view_tpl = 'page/rpc_default.tpl'; break;
			}
		}
	}
	else
	{
		// All authenticated and guest users get a template list
		$user_templates = RPC_User::get_templates($user);
		if ($user_templates) $smarty->assign('templates', $user_templates);

		// Real users (of all levels) get assignment lists
		if ($user->raw_perms_int >= RPC_User::RPC_AUTHLEVEL_USER)
		{
			$user_pending_assignments = $user->get_assignments(RPC_User::RPC_ASSIGNMENT_STATUS_ACTIVE);
			$user_old_assignments = $user->get_assignments(RPC_User::RPC_ASSIGNMENT_STATUS_EXPIRED);
			$user_todos = $user->get_items_due(7);
			if ($user_pending_assignments) $smarty->assign('assignments_pending', $user_pending_assignments);
			if ($user_old_assignments) $smarty->assign('assignments_old', $user_old_assignments);
			if ($user_todos) $smarty->assign('todos', $user_todos);

			// Authenticated users should immediately get their specific template
			$view_tpl = 'page/main_authenticated.tpl';
		}
		else
		{
			// For guest users, basic unauthenticated template
			$view_tpl = 'page/main_guest.tpl';
		}
	}
}
$smarty->skin_display($view_tpl);
RPC::cleanup();
?>
