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
 * AJAX handler script for step modifications
 */
require_once(__DIR__ . '/../../vendor/autoload.php');

// Application setup
$config = RPC_Config::get_instance();
$db = RPC_DB::get_connection($config);
$smarty = new RPC_Smarty($config);

RPC::init(RPC_User::RPC_AUTHLEVEL_USER, $config, $db);

$rpc_success = "FAIL";
$rpc_error = "";
$rpc_new_transid = "";
$rpc_result = "";
$user = RPC::get_active_user();

if (!empty($user->error))
{
	$rpc_error = $user->get_error();
}
else
{
	if (
			// Invalid tranisd
			(!array_key_exists('transid', $_POST) || (isset($_POST['transid']) && $_POST['transid'] != $_SESSION['transid']))
			// Action as defined by RPC_Step::ACTION_*
			|| (!array_key_exists('action', $_POST) || (isset($_POST['action']) && !ctype_digit(strval($_POST['action']))))
			// Step ID is positive int or 0
			|| (!array_key_exists('id', $_POST) || (isset($_POST['id']) && !ctype_digit(strval($_POST['id']))))
			// Generic value field to server all changing values
			|| !array_key_exists('val_1', $_POST)
			|| !array_key_exists('val_2', $_POST)
		)
	{
		$rpc_error = "Invalid request.";
	}
	else
	{
		$step = new RPC_Step($_POST['id'], $user, $config, $db);
		if (!empty($step->error))
		{
			$rpc_error = $step->get_error();
		}
		else
		{
			switch ($_POST['action'])
			{
				case RPC_Step::ACTION_MOVE_TO_POS:
					if ($step->position != intval($_POST['val_1']))
					{
						// For assignment steps, we need valid JSON duedates.
						// For templates, $_POST['val_2'] is blank
						if (!$step->is_template_step)
						{
							if (!$o = RPC_Assignment::parse_json_stepdates($_POST['val_2']))
							{
								$rpc_error = "Invalid request.";
							}
							$assign = new RPC_Assignment($step->parent, $user, $config, $db);
						}
						else
						{
							$assign = new RPC_Template($step->parent, $user, $config, $db);
							$o = NULL;
						}
						if (empty($assign->error))
						{
							if ($assign->move_step($_POST['id'], intval($_POST['val_1']), $o))
							{
								// Have to commit change now before normalizing.
								$assign->db->commit();
								$assign->normalize_steps();
								$assign->db->commit();
							}
						}
						if (!empty($assign->error) || !empty($step->error))
						{
							$rpc_error = $assign->get_error();
							$rpc_error .= " " . $step->get_error();
						}
					}
					break;
				case RPC_Step::ACTION_SET_TITLE:
					$step->title = trim($_POST['val_1']);
					$step->update();
					break;
				case RPC_Step::ACTION_SET_DESC:
					$step->description = trim($_POST['val_1']);
					$step->update();
					break;
				case RPC_Step::ACTION_SET_TEACHER_DESC:
					// Students can't update teacher data
					if ($user->type == "TEACHER")
					{
						$step->teacher_description = trim($_POST['val_1']);
						$step->update();
					}
					else
					{
						$step->error = RPC_Step::ERR_ACCESS_DENIED;
						$rpc_error = $step->get_error();
					}
					break;
				case RPC_Step::ACTION_SET_ANNOTATION:
					$step->annotation = trim($_POST['val_1']);
					$step->update();
					break;
				case RPC_Step::ACTION_SET_DUEDATE:
					// Due dates always set to just before midnight
					// arrive in format YYYYMMDD
					if (!$newdate = RPC_Assignment::validate_duedate($_POST['val_1']))
					{
						$rpc_error = "Invalid request.";
					}
					else
					{
						$step->set_due_date($newdate);
						$step->update();

						// If this is the last step, also extend the assignment's due date accordingly.
						$assign = new RPC_Assignment($step->parent, $user, $config, $db);
						if (!empty($assign->error))
						{
							$step->error = RPC_Step::ERR_CANNOT_CHANGE_ASSIGNMENT_DUEDATE;
						}
						else
						{
							// Match the last step array key to this step's id
							$arr_step_keys = array_keys($assign->steps);
							if ($step->id == array_pop($arr_step_keys))
							{
								$assign->set_due_date($newdate);
								$assign->update();
							}
						}
					}
					break;
				case RPC_Step::ACTION_SET_PERCENT:
					// Percent has to be an int between 0 and 100
					if (ctype_digit(strval($_POST['val_1'])) && $_POST['val_1'] >=0 && $_POST['val_1'] <= 100)
					{
						$step->percent = trim($_POST['val_1']);
						$step->update();
					}
					else
					{
						$step->error = RPC_Step::ERR_INVALID_INPUT;
					}
					break;
				case RPC_Step::ACTION_DELETE:
					// TODO: Add function to delete the last step.
					// For now, you're not allowed to delete the only step!
					// Normalize positions in the parent
					if ($step->is_template_step)
					{
						$assign = new RPC_Template($step->parent, $user, $config, $db);
					}
					else
					{
						$assign = new RPC_Assignment($step->parent, $user, $config, $db);
					}
					if (!empty($assign->error))
					{
						$step->error = RPC_Step::ERR_DB_ERROR;
					}
					else
					{
						if (count($assign->steps) > 1)
						{
							$step->delete();
							$assign->normalize_steps();
						}
						// Was the only step. Delete not permitted!
						else
						{
							$step->error = RPC_Step::ERR_CANNOT_DELETE_ONLY_STEP;
						}
					}
					break;
				case RPC_Step::ACTION_CREATE_STEP_AFTER:
					// Create a full assignment, shift steps up to make room and create a new one
					if ($step->is_template_step)
					{
						$assign = new RPC_Template($step->parent, $user, $config, $db);
						$assign_obj_type = RPC_Smarty::OBJECT_TYPE_TEMPLATE;
					}
					else
					{
						$assign = new RPC_Assignment($step->parent, $user, $config, $db);
						$assign_obj_type = RPC_Smarty::OBJECT_TYPE_ASSIGNMENT;
					}
					if (!empty($assign->error))
					{
						$rpc_error = $assign->get_error();
						break;
					}
					else
					{
						// Move all steps ahead of the one modified in the step editor up...
						if ($assign->shift_steps_up($step->position + 1))
						{
							$newstep = RPC_Step::create(
								$assign->id,
								$user,
								"Step title...",
								"Add this step's description here...",
								"Add this step's additional information for teachers and instructors here...",
								"",
								$step->position + 1,
								$step->due_date,
								NULL,
								$config,
								$db
							);
							// On successful creation, normalize the assignment
							if ($newstep)
							{
								$assign->steps[$newstep->id] = $newstep;
								$assign->normalize_steps();
								$assign->get_steps($user);
								// Smarty will create the HTML node for the new step
								$smarty = new RPC_Smarty($config);
								$smarty->set_user($user);
								$smarty->set_assignment($assign, $assign_obj_type);
								// Manually assign a $step smarty var, which will be used
								// by the step template (normally executed in a foreach loop)
								$smarty->set_step($newstep);
								$rpc_result = trim($smarty->skin_fetch('page/step_edit.tpl'));
							}
							else
							{
								$step->error = RPC_Step::ERR_DB_ERROR;
							}
						}
						else
						{
							// If steps can't be shifted around, must be a DB error
							$step->error = RPC_Step::ERR_DB_ERROR;
						}
						break;
					}
				default:
					$step->error = RPC_Step::ERR_INVALID_INPUT;
					break;
			}
			// Errors in action switch...
			if (!empty($step->error) || !empty($rpc_error))
			{
				$rpc_success = "FAIL";
				$rpc_error = !empty($step->error) ? $step->get_error() : $rpc_error;
				$db->rollback();
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
// Done, return
// New transid if successful
if ($rpc_success == "OK")
{
	$_SESSION['transid'] = md5(time() . rand());
}
$rpc_new_transid = $_SESSION['transid'];
header("Content-type: text/plain");
echo "$rpc_success|$rpc_error|$rpc_new_transid|$rpc_result";
RPC::cleanup();
?>
