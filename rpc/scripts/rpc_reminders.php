#!/usr/bin/php -q
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

// Running from web server not allowed!
if (PHP_SAPI !== "cli")
{
	exit(0);
}
require_once(__DIR__ . '/../../vendor/autoload.php');

// Application setup
$config = RPC_Config::get_instance();
$db = RPC_DB::get_connection($config);

// Get a list of pending notifications
$rpc_pending_notifications = RPC_Notification::get_pending_notifications($config, $db);
//var_dump($rpc_pending_notifications); exit();

if (count($rpc_pending_notifications) > 0)
{
	// We'll step through the list and create RPC_User and RPC_Assignment objects
	// when the current one changes from the previous.  That saves us constantly
	// recreating the same user or assignment when multiple notifications might
	// be going out.
	$rpc_current_userid = NULL;
	$rpc_current_user = NULL;
	$rpc_current_assignid = NULL;
	$rpc_current_linkid = NULL;
	$rpc_current_assignment = NULL;
	$rpc_current_step = NULL;
	$rpc_notification_successes = 0;
	$rpc_notification_failures = 0;
	foreach ($rpc_pending_notifications as $notification)
	{
		if ($notification['userid'] != $rpc_current_userid)
		{
			$rpc_current_userid = intval($notification['userid']);
			$rpc_current_user = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, $rpc_current_userid, $config, $db);
			if (!empty($rpc_current_user->error))
			{
				echo "ERROR: Userid {$rpc_current_userid} " . $rpc_current_user->get_error();
				$rpc_notification_failures++;
				continue;
			}
		}
		if ($notification['assignid'] != $rpc_current_assignid || $notification['linkid'] != $rpc_current_linkid)
		{
			$rpc_current_assignid = intval($notification['assignid']);
			$rpc_current_linkid = intval($notification['linkid']);
			// Assignment is loaded in context of $rpc_current_user
			if ($rpc_current_linkid > 0)
			{
				$rpc_current_assignment = new RPC_Linked_Assignment($rpc_current_linkid, $rpc_current_user, $config, $db);
			}
			else
			{
				$rpc_current_assignment = new RPC_Assignment($rpc_current_assignid, $rpc_current_user, $config, $db);
			}
			if (!empty($rpc_current_assignment->error))
			{
				echo "ERROR: Assignid {$rpc_current_assignid} " . $rpc_current_assignment->get_error();
				$rpc_notification_failures++;
				continue;
			}
		}
		// Load step from regular or linked assignment
		if ($rpc_current_linkid > 0)
		{
			$rpc_current_step = $rpc_current_assignment->assignment->steps[intval($notification['stepid'])];
		}
		else $rpc_current_step = $rpc_current_assignment->steps[intval($notification['stepid'])];
		if (!empty($rpc_current_step->error))
		{
			echo "ERROR: Stepid {$notification['stepid']} " . $rpc_current_step->get_error();
			$rpc_notification_failures++;
			continue;
		}

		// Send the notification
		$rpc_notification = new RPC_Notification($config);
		$rpc_notification->prepare($rpc_current_user, $rpc_current_assignment, $rpc_current_step);
		$rpc_notification->send();
		if (!empty($rpc_notification->error))
		{
			echo "ERROR: Notification send failed. " . $rpc_notification->get_error();
			$rpc_notification_failures++;
		}
		// On success, update notification date in the database
		else
		{
			$db->beginTransaction();
			if ($notification['linkid'] != NULL)
			{
				$update_date_result = $rpc_current_step->set_linked_notify_date($notification['linkid']);
			}
			else $update_date_result = $rpc_current_step->set_notify_date();
			if ($update_date_result)
			{
				echo "Sent notification for stepid {$rpc_current_step->id}\n";
				$rpc_notification_successes++;
				$db->commit();
			}
			else $db->rollBack();
		}
		// Either success or failure already, so elminate the RPC_Notification;
		unset($rpc_notification);
	}
	echo "Sent $rpc_notification_successes notifications with $rpc_notification_failures failures.\n";
	exit(0);
}
else
{
	echo "No pending notifications to send.\n";
	exit(0);
}
?>
