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
 * Administrative action for permission grant/revoke
 */
require_once(__DIR__ . '/../../vendor/autoload.php');

// Application setup
$config = RPC_Config::get_instance();
$db = RPC_DB::get_connection($config);
// Administrative access required.
RPC::init(RPC_User::RPC_AUTHLEVEL_ADMINISTRATOR, $config, $db);

$perms_success = "";
$perms_error_string = "";
$perms_new_transid = "";
if (
	(!isset($_POST['transid']) || (isset($_POST['transid']) && $_POST['transid'] != $_SESSION['transid'])) ||
	(!isset($_POST['id']) || (isset($_POST['id']) && !ctype_digit(strval(($_POST['id']))))) ||
	// action may be 'g' (grant) or 'r' (revoke)
	(!isset($_POST['action']) || (isset($_POST['action']) && $_POST['action'] !== 'g' && $_POST['action'] !== 'r')) ||
	// Grant must specify a|p permission
	((isset($_POST['action']) && $_POST['action'] === 'g') && (!isset($_POST['perm']) || (isset($_POST['perm']) && $_POST['perm'] !== 'a' && $_POST['perm'] !=='p')))
)
{
	$perms_error_string = "Invalid request.";
}
else
{
	$user = RPC::get_active_user();
	if (!empty($user->error))
	{
		$perms_error_string = $user->get_error();
	}
	// Access is okay...
	else
	{
		$perms_user = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, $_POST['id'], $config, $db);
		if (!empty($perms_user->error))
		{
			$perms_error_string = $perms_user->get_error();
		}
		else
		{
			$db->beginTransaction();
			// Set session user as authority to change perms
			$perms_user->set_active_authority_user($user);
			switch ($_POST['action'])
			{
				// Grant permission
				case 'g':
					switch ($_POST['perm'])
					{
						case 'p': $permtype = RPC_User::RPC_AUTHLEVEL_PUBLISHER; break;
						case 'a': $permtype = RPC_User::RPC_AUTHLEVEL_ADMINISTRATOR; break;
					}
					$perm_result = $perms_user->grant_permission($permtype);
					break;
				// Revoke permission
				case 'r': $perm_result = $perms_user->revoke_permission(); break;
			}
			if (empty($perms_user->error))
			{
				$db->commit();
				$perms_success = "OK";
			}
			else
			{
				$db->rollBack();
				$perms_success = "FAIL";
				$perms_error_string = $perms_user->get_error();
			}
		}
	}
}
// Spit out the result
if ($perms_success == "OK")
{
	$_SESSION['transid'] = md5(time() . rand());
}
$perms_new_transid = $_SESSION['transid'];
header("Content-type: text/plain");
echo "$perms_success|$perms_error_string|$perms_new_transid";
?>
