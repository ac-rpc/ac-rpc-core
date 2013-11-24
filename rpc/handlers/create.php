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
 * Form handler script for new assignment/template creation
 */
require_once(dirname(__FILE__) . '/../inc/rpc.inc.php');
require_once(dirname(__FILE__) . '/../inc/rpc_config.inc.php');
require_once(dirname(__FILE__) . '/../inc/rpc_db.inc.php');
require_once(dirname(__FILE__) . '/../inc/rpc_user.inc.php');
require_once(dirname(__FILE__) . '/../inc/rpc_step.inc.php');
require_once(dirname(__FILE__) . '/../inc/rpc_assignment.inc.php');
require_once(dirname(__FILE__) . '/../inc/rpc_template.inc.php');
require_once(dirname(__FILE__) . '/../inc/rpc_smarty.inc.php');

// Application setup
$config = RPC_Config::get_instance();
$db = RPC_DB::get_connection($config);
$smarty = new RPC_Smarty($config);

RPC::init(RPC_User::RPC_AUTHLEVEL_GUEST, $config, $db);

$_SESSION['general_error'] = "";
$user = RPC::get_active_user();

if ($user)
{
	if (!empty($user->error))
	{
		$_SESSION['general_error'] = $user->get_error();
	}
	$create_for_user = $user;
}
// No $user, so user is guest
else
{
	$user = new RPC_User(RPC_User::RPC_DO_NOT_QUERY, NULL, $config, $db);
	if (isset($_POST['isteacher']) && $_POST['isteacher'] == "on")
	{
		$user->type = "TEACHER";
		$_SESSION['active_assignment_usertype'] = "TEACHER";
	}
	else $_SESSION['active_assignment_usertype'] = "STUDENT";
	$create_for_user = NULL;
}
/* POST requirements:
 * transid: Matches value in $_SESSION
 * start: [0-9]{8} and valid mktime()
 * due: [0-9]{8} and valid mktime()
 * title: nonempty
 * astemplate: May not exist (checkbox)
 * template: ctype_digit() or "BLANK"
 *
 * The transid and needs to be checked outside the creation function.
 * RPC_Assignment::create_blank() and RPC_Assignment::clone_from_template() will validate
 * these params, but expect the dates to already be UNIX timestamps.
 */

if (!isset($_POST['astemplate']))
{
	$rpc_astemplate = FALSE;
	$rpc_startdate = RPC_Assignment::validate_startdate($_POST['start']);
	$rpc_duedate = RPC_Assignment::validate_startdate($_POST['due']);
}
else
{
	$rpc_astemplate = TRUE;
	$rpc_startdate = NULL;
	$rpc_duetdate = NULL;
}
if
(
	((!$rpc_astemplate && $rpc_startdate && $rpc_duedate && ($rpc_duedate >= $rpc_startdate)) || $rpc_astemplate)
	&& ($_POST['transid'] == $_SESSION['transid'])
	&& (ctype_digit(strval($_POST['template'])) || $_POST['template'] === "BLANK")
)
{
	if ($_POST['template'] === "BLANK")
	{
		if ($rpc_astemplate)
		{
			// Permissions to create template are checked by the creation method.
			$new_object = RPC_Template::create_blank($create_for_user, $_POST['title'], $_POST['class'], TRUE, $config, $db);
		}
		else
		{
			$new_object = RPC_Assignment::create_blank($create_for_user, $_POST['title'], $_POST['class'], $rpc_startdate, $rpc_duedate, TRUE, $config, $db);
		}
	}
	else
	{
		$clone_obj = new RPC_Template($_POST['template'], $user, $config, $db);
		if (!empty($clone_obj->error))
		{
			$new_object = RPC_Assignment::create_blank(NULL, NULL, $config, $db);
			$new_object->error = $clone_obj->error;
			$_SESSION['general_error'] = $clone_obj->get_error();
		}
		else
		{
			if ($rpc_astemplate)
			{
				// Permissions to create template are checked by the creation method.
				$new_object = RPC_Template::clone_from_template($clone_obj, $create_for_user, $_POST['title'], $_POST['class'], $config, $db);
			}
			else
			{
				$new_object = RPC_Assignment::clone_from_template($clone_obj, $create_for_user, $_POST['title'], $_POST['class'], $rpc_startdate, $rpc_duedate, $config, $db);
				// Unauthenticated user must have assignment stored in _SESSION
				// since it can't be stuffed in the db.
				if ($create_for_user === NULL)
				{
					$new_object->store_to_session();
					header("Location: " . $config->app_fixed_web_path);
					exit();
				}

			}
		}
	}
	if (!empty($new_object->error))
	{
		$_SESSION['general_error'] = $new_object->get_error();
		header("Location: " . $config->app_fixed_web_path);
		exit();
	}
	else
	{
		unset($_SESSION['transid']);
		header("Location: " . $new_object->url);
		exit();
	}
}
else
{
	$_SESSION['general_error'] = "Invalid submission.";
	header("Location: " . $config->app_fixed_web_path);
	RPC::cleanup();
	exit();
}
?>
