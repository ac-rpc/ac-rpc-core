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
 * AJAX handler script for assignment modifications
 */
require_once(__DIR__ . '/../../vendor/autoload.php');

// Application setup
$config = RPC_Config::get_instance();
$db = RPC_DB::get_connection($config);
$smarty = new RPC_Smarty($config);

// To clear a session assignment, no login is required.
// TODO: Find a better way to handle this.
//       The RPC_Assignment::ACTION_CLONE should take place on handlers/create.php
//       instead of here to separate the creation and assignment actions
if ((isset($_POST['action']) && $_POST['action'] == RPC_Assignment::ACTION_CLEAR) && (isset($_POST['type']) && $_POST['type'] === "assignment"))
{
	RPC::init(RPC_User::RPC_AUTHLEVEL_GUEST, $config, $db);
}
else
{
	RPC::init(RPC_User::RPC_AUTHLEVEL_USER, $config, $db);
}


// If a 'submit' was sent in $rpc_post, this was a regular form submission and not AJAX
$rpc_success = "FAIL";
$rpc_error = "";
$rpc_new_transid = "";
$rpc_result = "";
$rpc_return_assign = "";

RPC::store_handler_post();
$user = RPC::get_active_user();
$rpc_post = RPC::retrieve_handler_post();

$rpc_submit_type = array_key_exists('submit', $rpc_post) ? "FORM" : "AJAX";
if (!empty($user->error))
{
	$rpc_error = $user->get_error();
}
else
{
	if (
			// Invalid tranisd
			(!array_key_exists('transid', $rpc_post) || (isset($rpc_post['transid']) && $rpc_post['transid'] != $_SESSION['transid']))
			// Action as defined by RPC_Step::ACTION_*
			|| (!array_key_exists('action', $rpc_post) || (isset($rpc_post['action']) && !ctype_digit(strval($rpc_post['action']))))
			// Step ID is positive int or blank for guest assignments posted for save
			// Validity is checked on object creation.
			|| (!array_key_exists('id', $rpc_post))
			// Value must be set, but may be empty
			|| (!array_key_exists('val', $rpc_post))
			// Type must be "link", "assignment" or "template"
			|| (!array_key_exists('type', $rpc_post) || (isset($rpc_post['type']) && !in_array($rpc_post['type'], array("assignment", "template", "link"))))
		)
	{
		$rpc_error = "Invalid request.";
	}
	else
	{
		// Create the correct type of object
		switch ($rpc_post['type'])
		{
			case "assignment":
				// Blank id indicates a guest assignment
				// Restore from $_SESSION if it's there, then delete it from session
				if (empty($rpc_post['id']))
				{
					if ($active_obj = RPC_Assignment::retrieve_from_session($config, $db))
					{
						RPC_Assignment::delete_from_session();
					}
					else
					{
						$rpc_error = "Invalid input";
					}
				}
				else
				{
					$active_obj = new RPC_Assignment($rpc_post['id'], $user, $config, $db); break;
				}
				break;
			case "template":
				$active_obj = new RPC_Template($rpc_post['id'], $user, $config, $db);
				break;
			case "link":
				$active_obj = new RPC_Linked_Assignment($rpc_post['id'], $user, $config, $db);
				break;
			default: $rpc_error = "Invalid input"; break;
		}

		if (!isset($active_obj) || !empty($rpc_error) || (isset($active_obj) && !empty($active_obj->error)))
		{
			$rpc_error = isset($active_obj) ? $active_obj->get_error() : $rpc_error;
		}
		else
		{
			$db->beginTransaction();
			switch ($rpc_post['action'])
			{
				case RPC_Assignment::ACTION_CLEAR:
					$rpc_return_assign = $config->app_fixed_web_path;
					// Should technically allready be deleted, but this call doesn't hurt.
					RPC_Assignment::delete_from_session();
					break;
				case RPC_Assignment::ACTION_DELETE:
					$rpc_return_assign = $config->app_fixed_web_path;
					$active_obj->delete();
					if (empty($active_obj->error))
					{
						$_SESSION['general_success'] = "Your {$rpc_post['type']} was successfully deleted.";
					}
					break;
				case RPC_Assignment::ACTION_LINK:
					if (get_class($active_obj) == "RPC_Assignment")
					{
						if (!RPC_Linked_Assignment::exists($active_obj->id, $user->id, $db))
						{
							if ($new_link = RPC_Linked_Assignment::create($active_obj, $user, $db))
							{
								$rpc_return_assign = RPC_Linked_Assignment::get_url($new_link, "", $config);
								$_SESSION['general_success'] = "You have saved a link to this assignment.";
							}
							else
							{
								$rpc_error = "Error: Link creation failed.";
							}
						}
						else $rpc_error = "Error: You already have a link to this assignment.";
					}
					else $rpc_error = "Error: You cannot save a link to a template.";
					if (!empty($rpc_error)) $rpc_return_assign = $active_obj->url;
					break;
				case RPC_Linked_Assignment::ACTION_SET_LINKED_STEP_ANNOTATION:
					// val_2 has to be a valid stepid within $active_obj->assignment
					if (ctype_digit(strval($_POST['val_2'])) && array_key_exists($_POST['val_2'], $active_obj->assignment->steps))
					{
						// val_2 might be empty.
						$active_obj->assignment->steps[$_POST['val_1']]->annotation = $_POST['val_1'];
						$active_obj->update_step($_POST['val_2']);
					}
					else
					{
						$active_obj->error = RPC_Linked_Assignment::ERR_INVALID_INPUT;
					}
					break;
				case RPC_Assignment::ACTION_CLONE:
					// Clone assignment. On failure, redirect to original assignment
					// Otherwise direct to new assignment
					// active_obj is not allowed to be a template!
					if (get_class($active_obj) == "RPC_Assignment")
					{
						$new_assign = RPC_Assignment::clone_from_assignment($active_obj, $user, $config, $db);
					}
					else
					{
						$new_assign = new RPC_Assignment(NULL, NULL, $config, $db);
						$new_assign->error = RPC_Assignment::ERR_INVALID_INPUT;
					}
					if (empty($new_assign->error) && empty($rpc_error))
					{
						$rpc_return_assign = $new_assign->url;
						$_SESSION['general_success'] = "Your assignment was successfully saved.";
					}
					else
					{
						$rpc_return_assign = $active_obj->url;
						$active_obj->error = $new_assign->error;
					}
					break;
				case RPC_Assignment::ACTION_SET_TITLE:
					$active_obj->title = trim($rpc_post['val']);
					$active_obj->update();
					break;
				case RPC_Assignment::ACTION_SET_DESC:
					$active_obj->title = trim($rpc_post['val']);
					$active_obj->update();
					break;
				case RPC_Assignment::ACTION_SET_NOTIFY:
					if (intval($rpc_post['val']) == 0 || intval($rpc_post['val']) == 1)
					{
						$active_obj->send_reminders = $rpc_post['val'] == 1 ? TRUE : FALSE;
						$active_obj->update();
					}
					else
					{
						$active_obj->error = RPC_Assignment::ERR_INVALID_INPUT;
					}
					break;
				case RPC_Assignment::ACTION_SET_SHARED:
					if (intval($rpc_post['val']) == 0 || intval($rpc_post['val']) == 1)
					{
						$active_obj->is_shared = $rpc_post['val'] == 1 ? TRUE : FALSE;
						$active_obj->update();
					}
					else
					{
						$active_obj->error = RPC_Assignment::ERR_INVALID_INPUT;
					}
					break;
				case RPC_Assignment::ACTION_SET_STARTDATE:
					if (!$newdate = RPC_Assignment::validate_startdate($rpc_post['val']))
					{
						$active_obj->error = RPC_Assignment::ERR_INVALID_INPUT;
					}
					else
					{
						$active_obj->set_start_date($newdate);
						$active_obj->update();
						// Dates get recalculated according to step percentages
						$active_obj->calculate_stepdates($user, $active_obj->start_date, $active_obj->due_date);
						// Recalculated dates sent back to client as JSON
						$rpc_result = $active_obj->encode_json_stepdates();
					}
					break;
				case RPC_Assignment::ACTION_SET_DUEDATE:
					if (!$newdate = RPC_Assignment::validate_duedate($rpc_post['val']))
					{
						$active_obj->error = RPC_Assignment::ERR_INVALID_INPUT;
					}
					else
					{
						$active_obj->set_due_date($newdate);
						$active_obj->update();
						// Dates get recalculated according to step percentages
						$active_obj->calculate_stepdates($user, $active_obj->start_date, $active_obj->due_date);
						// Recalculated dates sent back to client as JSON
						$rpc_result = $active_obj->encode_json_stepdates();
					}
					break;
				/*****************************************************
				 * Template actions
				 */
				case RPC_Template::ACTION_SET_PUBLISHED:
						$active_obj->is_published = $rpc_post['val'] == 1 ? TRUE : FALSE;
						$active_obj->update();
					break;
				default:
					$rpc_error = "Invalid request.";
					break;
			}
			// Errors in action switch...
			if (!empty($active_obj->error) || !empty($rpc_error))
			{
				$rpc_success = "FAIL";
				$rpc_error = !empty($active_obj->error) ? $active_obj->get_error() : $rpc_error;
				$db->rollBack();
			}
			// Successful action
			else
			{
				$rpc_success = "OK";
				$db->commit();
			}
		}
	}
}
RPC::cleanup();

// Form submissions get redirect
if ($rpc_submit_type == "FORM")
{
	$_SESSION['general_error'] = $rpc_error;
	header("Location: $rpc_return_assign");
	exit();
}
// AJAX submissions echo out result
else
{
	// Done, return
	// New transid if successful
	if ($rpc_success == "OK")
	{
		$_SESSION['transid'] = md5(time() . rand());
	}
	$rpc_new_transid = $_SESSION['transid'];

	header("Content-type: text/plain");
	echo "$rpc_success|$rpc_error|$rpc_new_transid|$rpc_result";
}
?>
