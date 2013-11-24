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
 * Administrator AJAX handler for user searching.
 * Outputs pipe-delimited text as username|email|name|is_publisher|is_administrator|error_string
 */
require_once(dirname(__FILE__) . '/../inc/rpc.inc.php');
require_once(dirname(__FILE__) . '/../inc/rpc_config.inc.php');
require_once(dirname(__FILE__) . '/../inc/rpc_db.inc.php');
require_once(dirname(__FILE__) . '/../inc/rpc_user.inc.php');

// Application setup
$config = RPC_Config::get_instance();
$db = RPC_DB::get_connection($config);
// Administrative access required.
RPC::init(RPC_User::RPC_AUTHLEVEL_ADMINISTRATOR, $config, $db);

$return = "";
$search_userid = "";
$search_username = "";
$search_email = "";
$search_name = "";
$search_is_publisher = "";
$search_is_administrator = "";
$search_error_string = "";

if (!isset($_GET['username']) || (isset($_GET['username']) && empty($_GET['username'])))
{
	$search_error_string = "Invalid search.";
}
else
{
	$user = RPC::get_active_user();
	if ($user->error === RPC_User::ERR_ACCESS_DENIED)
	{
		$search_error_string = $user->get_error();
	}
	// Access is okay...
	else
	{
		$search_user = new RPC_User(RPC_User::RPC_QUERY_USER_BY_USERNAME, $_GET['username'], $config, $db);
		if (!empty($search_user->error))
		{
			$search_error_string = $search_user->get_error();
		}
		else
		{
			$search_userid = $search_user->id;
			$search_username = $search_user->username;
			$search_email = $search_user->email;
			$search_name = $search_user->name;
			$search_is_publisher = $search_user->is_publisher ? "true" : "false";
			$search_is_administrator = $search_user->is_administrator ? "true" : "false";
		}
	}
}
// Spit out the result
header("Content-type: text/plain");
echo "$search_userid|$search_username|$search_email|$search_name|$search_is_publisher|$search_is_administrator|$search_error_string";
?>
