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

define('BASE_DIR', dirname(__FILE__) . '/../');
require_once(BASE_DIR . '/inc/rpc.inc.php');
require_once(BASE_DIR . '/inc/rpc_config.inc.php');
require_once(BASE_DIR . '/inc/rpc_db.inc.php');
require_once(BASE_DIR . '/inc/rpc_smarty.inc.php');
require_once(BASE_DIR . '/inc/rpc_user.inc.php');

// Application setup
$config = RPC_Config::get_instance();
$db = RPC_DB::get_connection($config);
// Administrative access required.
RPC::init(RPC_User::RPC_AUTHLEVEL_ADMINISTRATOR, $config, $db);
$smarty = new RPC_Smarty($config);

$user = RPC::get_active_user();
$smarty->set_user($user);
if ($user->error === RPC_User::ERR_ACCESS_DENIED)
{
	$smarty->skin_display('page/rpc_access_denied.tpl');
	exit();
}
$_SESSION['transid'] = md5(time() . rand());
$smarty->assign('transid', $_SESSION['transid']);

// Get privileged users for the admin list
$arr_privileged_users = RPC_User::get_all_privileged_users($config, $db);
$smarty->assign('privileged_users', $arr_privileged_users);



// Access is okay, start setting up administrative options
$smarty->skin_display('page/admin.tpl');

?>
