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

require_once('rpc.inc.php');
require_once('rpc_assignment.inc.php');
require_once('rpc_step.inc.php');
require_once('rpc_user.inc.php');
/**
 * Class RPC_Linked_Assignment
 * Assignment linked for users who don't own them
 *
 * @package RPC
 * @version $id$
 * @author Michael Berkowski <mjb@umn.edu>
 */
class RPC_Linked_Assignment
{	const ERR_NO_SUCH_OBJECT = 11;
	const ERR_INVALID_INPUT = 12;
	const ERR_ACCESS_DENIED = 13;
	const ERR_DB_ERROR = 14;
	const ERR_DATA_UNSANITIZED = 15;
	const ERR_CANNOT_RETRIEVE_ASSIGNMENT = 16;
	const ERR_NO_SUCH_ASSIGNMENT = 17;
	const ERR_NO_SUCH_STEP = 18;

	const ACTION_DELETE = 2;
	const ACTION_SET_NOTIFY = 13;
	const ACTION_SET_LINKED_STEP_ANNOTATION = 31;
	
	/**
	 * Unique id of link
	 * 
	 * @var integer
	 * @access public
	 */
	public $id;
	/**
	 * Linked assignment object
	 * 
	 * @var RPC_Assignment
	 * @access public
	 */
	public $assignment;
	/**
	 * Does the active user have permission to edit this object?
	 *
	 * @var boolean
	 * @access public
	 */
	public $is_editable = FALSE;
	/**
	 * Should email reminders be sent for this linked assignment?
	 * 
	 * @var boolean
	 * @access public
	 */
	public $send_reminders;
	/**
	 * Valid actions for this object type
	 *
	 * @var array
	 * @access public
	 * @static
	 */
	public $valid_actions = array('','edit','delete');
	/**
	 * URL of linked assignment
	 * 
	 * @var string
	 * @access public
	 */
	public $url;
	/**
	 * Edit URL of linked assignment
	 * 
	 * @var string
	 * @access public
	 */
	public $url_edit;
	/**
	 * Delete URL of linked assignment
	 * 
	 * @var string
	 * @access public
	 */
	public $url_delete;
	/**
	 * Error condition
	 * 
	 * @var integer
	 * @access public
	 */
	public $error = NULL;
	/**
	 * MySQLi database connection singleton
	 *
	 * @var object MySQLi database connection
	 * @access public
	 */
	public $db;
	/**
	 * Global RPC_Config configuration singleton
	 *
	 * @var object RPC_Config
	 * @access public
	 */
	public $config;

	/**
	 * __construct 
	 * 
	 * @param integer $id Unique id of link
	 * @param object $user RPC_User
	 * @param object $config RPC_Config global configuration singleton
	 * @param mixed $db MySQLi global database singleton
	 * @access public
	 * @return RPC_Linked_Assignment
	 */
	public function __construct($id, $user, $config, $db)
	{
		$this->config = $config;
		$this->db = $db;

		if (!ctype_digit(strval($id)))
		{
			$this->error = self::ERR_DB_ERROR;
			return;
		}

		$this->id = $id;
		$qry = sprintf("SELECT assignid, userid, remind FROM linked_assignments WHERE linkid = %u;", $this->id);
		if ($result = $this->db->query($qry))
		{
			if ($result->num_rows == 1)
			{
				$row = $result->fetch_assoc();

				// Check ownership. Only owner can view a linked assignment
				if ($user == NULL || $row['userid'] != $user->id)
				{
					$this->error = self::ERR_ACCESS_DENIED;
				}
				else
				{
					$this->is_editable = TRUE;
					$this->send_reminders = $row['remind'] == 1 ? TRUE : FALSE;
					$assignid = $row['assignid'];
					$result->close();
					if (RPC_Assignment::exists($assignid, $this->db))
					{
						$this->assignment = new RPC_Assignment($assignid, $user, $config, $db);
					}
					else
					{
						$this->error = self::ERR_NO_SUCH_OBJECT;
					}
					if (!empty($this->assignment->error))
					{
						// Assignment may have been deleted by owner
						if ($this->assignment->error == RPC_Assignment::ERR_NO_SUCH_OBJECT)
						{
							$this->error = self::ERR_NO_SUCH_ASSIGNMENT;
						}
						// Generic error we'll deal with later
						// TODO: Deal with this later!
						else
						{
							$this->error = self::ERR_CANNOT_RETRIEVE_ASSIGNMENT;
						}
					}
				}
			}
			else
			{
				$this->error = self::ERR_NO_SUCH_OBJECT;
			}

			// No problems, so far... Add additional parameters
			if (empty($this->error))
			{
				$this->url = self::get_url($this->id, "", $this->config);
				$this->url_edit = self::get_url($this->id, "edit", $this->config);
				$this->url_delete = self::get_url($this->id, "delete", $this->config);

				// Attach the linked assignment annotations to the assignment
				$steps_qry = sprintf("SELECT stepid, annotation, remindersentdate FROM linked_steps WHERE linkid = %u;", $this->id);
				if ($steps_result = $this->db->query($steps_qry))
				{
					$arr_step_annotations = array();
					while ($row = $steps_result->fetch_assoc())
					{
						$arr_step_annotations[$row['stepid']]['annotation'] = $row['annotation'];
						$arr_step_annotations[$row['stepid']]['reminder_sent_date'] = $row['remindersentdate'];
					}
					$steps_result->close();

					// Bind the annotations and reminder sent dates to the assignment steps
					foreach ($this->assignment->steps as $step)
					{
						if (isset($arr_step_annotations[$step->id]))
						{
							$step->annotation = $arr_step_annotations[$step->id]['annotation'];
							$step->reminder_sent_date = $arr_step_annotations[$step->id]['reminder_sent_date'];
						}
					}
				}
				else
				{
					$this->error = self::ERR_DB_ERROR;
				}
			}
		}
		else
		{
			$this->error = self::ERR_DB_ERROR;
		}
		return;
	}
	/**
	 * Retrieve user annotations and reminder dates for linked steps and attach them to $this->assignment->steps
	 * 
	 * @access private
	 * @return boolean
	 */
	private function _get_step_annotations()
	{
		if (!isset($this->assignment))
		{
			$this->error = self::ERR_NO_SUCH_ASSIGNMENT;
			return FALSE;
		}
		$qry = sprintf("SELECT id, annotation, reminder_sent_date FROM linked_steps_view WHERE linkid = %u;", $this->id);
		if ($result = $this->db->query($qry))
		{
			while ($row = $result->fetch_assoc())
			{
				$this->steps[$row['id']]->annotation = $row['annotation'];
				$this->steps[$row['id']]->reminder_sent_date = $row['reminder_sent_date'];
			}
			$result->close();
		}
		return TRUE;
	}
	/**
	 * Delete the linked assignment and all associated annotations
	 * 
	 * @access public
	 * @return boolean
	 */
	public function delete()
	{
		$qry = sprintf("DELETE FROM linked_assignments WHERE linkid = %u;", $this->id);
		echo $qry;
		if ($result = $this->db->query($qry))
		{
			return TRUE;
		}
		else return FALSE;
	}
	/**
	 * Save the current state to the database
	 * Right now, the only thing to change is send_reminders
	 * 
	 * @access public
	 * @return boolean
	 */
	public function update()
	{
		$this->sanitize();
		if (!$this->_is_sanitized)
		{
			$this->error = self::ERR_INVALID_INPUT;
			return FALSE;
		}
		$qry = sprintf("UPDATE linked_assignments SET remind = %u WHERE linkid = %u;", $this->send_reminders === TRUE ? 1 : 0, $this->id);
		if ($result = $this->db->query($qry))
		{
			return TRUE;
		}
		else
		{
			$this->error = self::ERR_DB_ERROR;
			return FALSE;
		}
	}
	public function update_step($stepid)
	{
		if (!array_key_exists($stepid, $this->assignment->steps))
		{
			$this->error = self::ERR_NO_SUCH_STEP;
			return FALSE;
		}
		if (!$this->sanitize_step($stepid)) return FALSE;

		if (!array_key_exists($stepid, $this->steps))
		{
			$this->error = self::ERR_NO_SUCH_STEP;
			return FALSE;
		}
		$qry = sprintf("UPDATE linked_steps SET annotation = '%s' WHERE linkid = %u and stepid = %u;", $this->steps[$stepid]->annotation, $this->id, $stepid);
		if ($result = $this->db->query($qry))
		{
			return TRUE;
		}
		else
		{
			$this->error = self::ERR_DB_ERROR;
			return FALSE;
		}
	}
	/**
	 * Sanitize before database update
	 * Right now, there's nothing much to do.
	 * 
	 * @access public
	 * @return void
	 */
	public function sanitize()
	{
		$this->_is_sanitized = TRUE;
		return $this->_is_sanitized;
	}
	public function sanitize_step($stepid)
	{
		$this->steps[$stepid]->annotation = RPC_Step::step_strip_tags($this->steps[$stepid]->annotation);
		$this->steps[$stepid]->annotation = trim($this->db->real_escape_string($this->steps[$stepid]->annotation));
		return TRUE;
	}
	/**
	 * Return an error string
	 * 
	 * @access public
	 * @return string
	 */
	public function get_error()
	{
		if (!empty($this->error))
		{
			switch ($this->error)
			{
				case self::ERR_NO_SUCH_OBJECT: return "Linked assignment could not be found.";
				case self::ERR_INVALID_INPUT: return "Invalid input.";
				case self::ERR_ACCESS_DENIED: return "You do not have permission to view or change this item.";
				case self::ERR_DB_ERROR: return "A database error occurred";
				case self::ERR_DATA_UNSANITIZED: return "Input data has not been sanitized.";
				case self::ERR_CANNOT_RETRIEVE_ASSIGNMENT: "There was an error retrieving the requested assignment.";
				case self::ERR_NO_SUCH_ASSIGNMENT: return "The requested assignment does not exist or may have been deleted.";
				case self::ERR_NO_SUCH_STEP: return "The requested step does not exist or may have been deleted.";
				default: return "";
			}
		}
		return;
	}
	/**
	 * Create a linked assignment for $user
	 * 
	 * @param object $assignment RPC_Assignment
	 * @param object $user RPC_User
	 * @param object $db MySQLi global database connection
	 * @static
	 * @access public
	 * @return boolean
	 */
	public static function create($assignment, $user, $db)
	{
		$qry = sprintf("INSERT INTO linked_assignments (assignid, userid) VALUES (%u, %u);", $assignment->id, $user->id);
		if ($result = $db->query($qry))
		{
			$new_linkid = $db->insert_id;
			$arr_row_values = array();
			foreach ($assignment->steps as $step)
			{
				$arr_row_values[] = sprintf("(%u,%u)", $new_linkid, $step->id);
			}
			$steps_qry = "INSERT INTO linked_steps (linkid, stepid) VALUES " . implode(",", $arr_row_values) . ";";
			if ($steps_result = $db->query($steps_qry))
			{
				return $new_linkid;
			}
			else
			{
				return FALSE;
			}
		}
		else return FALSE;
	}
	/**
	 * Test if assignment assignid has already been linked for user
	 * 
	 * @param integer $assignid 
	 * @param integer $user 
	 * @param object $db MySQLi global database connection
	 * @static
	 * @access public
	 * @return boolean
	 */
	public static function exists($assignid, $userid, $db)
	{
		$qry = sprintf("SELECT linkid FROM linked_assignments WHERE assignid = %u AND userid = %u;", $assignid, $userid);
		if ($result = $db->query($qry))
		{
			$rows = $result->num_rows;
			$result->close();
			return $rows > 0 ? TRUE : FALSE;
		}
		else return FALSE;
	}
	/**
	 * Build a URL to linked assignment $id 
	 * 
	 * @param integer $id 
	 * @param string $type URL type (""|"edit"|"delete") 
	 * @param object $config
	 * @static
	 * @access public
	 * @return string
	 */
	public static function get_url($id, $type, $config)
	{
		if (ctype_digit(strval($id)))
		{
			$type = strtolower($type);
			$action = in_array($type, array('','edit','delete')) ? $type : "";

			$url = "";
			if ($config->app_use_url_rewrite)
			{
				$url = $config->app_fixed_web_path . "links/" . $id;
				if ($action !== "") $url .= "/$action";
			}
			else 
			{
				$url = $config->app_fixed_web_path . "?link=" . $id;
				if ($action !== "") $url .= "&action=$action";
			}
			return $url;
		}
		else return "";
	}
}
 ?>
