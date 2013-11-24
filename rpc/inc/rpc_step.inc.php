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

require_once('rpc_step_base.inc.php');
/**
 * RPC_Step
 * Assignment step description
 *
 * @uses RPC_Step_Base
 * @package RPC
 * @version $id$
 * @author Michael Berkowski <mjb@umn.edu>
 */
class RPC_Step extends RPC_Step_Base
{
	/**
	 * Percent of time spent on this step if $parent is a template
	 *
	 * @var integer
	 * @access public
	 */
	public $percent;
	/**
	 * Is this step part of a template
	 *
	 * @var boolean
	 * @access public
	 */
	public $is_template_step;
	/**
	 * Date/time reminder should be sent for this step (Unix timestamp)
	 *
	 * @var integer
	 * @access public
	 */
	public $due_date;
	/**
	 * Number of days remaining until $due_date
	 *
	 * @var integer
	 * @access public
	 */
	public $days_left;
	/**
	 * Date/time reminder was sent for this step (Unix timestamp)
	 *
	 * @var integer
	 * @access public
	 */
	public $reminder_sent_date;
	/**
	 * Is this step part of a shared assignment (Viewable by users other than $this->author)?
	 *
	 * @var boolean
	 * @access public
	 */
	public $is_shared;

	/**
	 * Query skeleton for database INSERT
	 */
	const INSERT_QUERY =
		"INSERT INTO steps (
				assignid,
				title,
				description,
				teacher_description,
				annotation,
				position,
				reminderdate,
				percent
			) VALUES (%u, '%s', '%s', '%s', '%s', %u, FROM_UNIXTIME(%u), %u);";
	/**
	 * Query skeleton for database UPDATE
	 */
	const UPDATE_QUERY =
		"UPDATE steps SET
			title = '%s',
			description = '%s',
			teacher_description = '%s',
			annotation = '%s',
			reminderdate = FROM_UNIXTIME(%u),
			remindersentdate = FROM_UNIXTIME(%u),
			percent = %u
		WHERE stepid = %u;";
	/**
	 * Constructor returns step having stepid $id or an empty instance (with $db and $config) if $id===NULL
	 *
	 * @param integer $id Unique ID of step to retrieve.  If NULL, an empty object will be returned
	 * @param object $user RPC_User attempting to access this step
	 * @param object $config RPC_Config configuration singleton
	 * @param object $db MySQLi database connection singleton
	 * @access public
	 * @return object RPC_Step
	 */
	public function __construct($id=NULL, $user=NULL, $config, $db)
	{
		$this->config = $config;
		$this->db = $db;

		if ($id !== NULL)
		{
			if (!ctype_digit(strval($id)))
			{
				$this->error = self::ERR_INVALID_INPUT;
				return;
			}

			$qry = sprintf(<<<QRY
			SELECT
				id,
				assignid,
				userid,
				position,
				step,
				description,
				teacher_description,
				annotation,
				due_date,
				days_left,
				reminder_sent_date,
				is_shared,
				percent,
				template_step
			FROM steps_vw
			WHERE id = %u;
QRY
			, $id);

			if ($result = $this->db->query($qry))
			{
				if ($result->num_rows === 1)
				{
					$row = $result->fetch_assoc();

					// First get author and sharing information
					$this->id = intval($row['id']);
					$this->is_shared = $row['is_shared'] == 1 ? TRUE : FALSE;
					$this->is_template_step = $row['template_step'] == 1 ? TRUE : FALSE;
					$this->author = intval($row['userid']);
					// Check that the user accessing has permission
					// Anyone can view if $this->is_shared (though maybe not edit)
					// Anyone can view any template step
					if (!$this->is_template_step && (!$this->is_shared && ($user->id == NULL || (isset($user->id) && $user->id !== $this->author))))
					{
						$this->error = self::ERR_ACCESS_DENIED;
					}
					if (empty($this->error))
					{
						if ($this->is_template_step && ($user->is_administrator || ($user->is_publisher && $this->author == $user->id)))
						{
							$this->is_editable = TRUE;
						}
						else
						{
							$this->is_editable = $this->author == $user->id ? TRUE : FALSE;
						}
						$this->parent = intval($row['assignid']);
						$this->position = intval($row['position']);
						$this->title = $row['step'];
						$this->description = $row['description'];
						$this->teacher_description = $row['teacher_description'];
						// Annotation is only loaded for the owning user
						$this->annotation = $this->author == $user->id ? $row['annotation'] : NULL;
						$this->due_date = intval($row['due_date']);
						$this->days_left = intval($row['days_left']);
						// Reminder sent only applies to owning user.
						$this->reminder_sent_date = $this->author == $user->id ?intval($row['reminder_sent_date']) : NULL;
						$this->percent = intval($row['percent']);
					}
				}
				else
				{
					$this->error = self::ERR_NO_SUCH_STEP;
				}
				$result->close();
			}
			else
			{
				$this->error = self::ERR_DB_ERROR;
			}
		}
		// If not retrieving a DB step, we don't need to do anything else
		return;
	}

	/**
	 * Permanently delete step
	 * Note: Deleting the parent assignment will cause a cascading delete of associated steps.
	 *
	 * @access public
	 * @return boolean
	 */
	public function delete()
	{
		if (!$this->is_editable)
		{
			$this->error = self::ERR_ACCESS_DENIED;
			return FALSE;
		}
		$qry = sprintf("DELETE FROM steps WHERE stepid=%u;", $this->id);
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
	 * Update the notification date for this step
	 *
	 * @access public
	 * @return boolean
	 */
	public function set_notify_date()
	{
		$qry = sprintf("UPDATE steps SET remindersentdate = FROM_UNIXTIME(%u) WHERE stepid = %u;", time(), $this->id);
		if ($this->db->query($qry))
		{
			$this->reminder_sent_date = time();
			return TRUE;
		}
		else
		{
			$this->error = self::ERR_DB_ERROR;
			return FALSE;
		}
	}
	/**
	 * Update notification date for a linked assignment step
	 *
	 * @param integer $linkid
	 * @access public
	 * @return boolean
	 */
	public function set_linked_notify_date($linkid)
	{
		if (!ctype_digit(strval($linkid)))
		{
			$this->error = self::ERR_INVALID_INPUT;
		}
		$qry = sprintf("UPDATE linked_steps SET remindersentdate = FROM_UNIXTIME(%u) WHERE stepid = %u AND linkid = %u;", time(), $this->id, $linkid);
		if ($this->db->query($qry))
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
	 * Clear/unset the notification_sent_date so the notification can be resent.
	 * This is done for both steps and linked_steps
	 *
	 * @access public
	 * @return void
	 */
	public function clear_notify_date()
	{
		$qry = sprintf("UPDATE steps SET remindersentdate = NULL WHERE stepid = %u;", $this->id);
		$qry_linked = sprintf("UPDATE linked_steps SET remindersentdate = NULL WHERE stepid = %u;", $this->id);
		if ($this->db->query($qry) && $this->db->query($qry_linked))
		{
			$this->reminder_sent_date = NULL;
			return TRUE;
		}
		else
		{
			$this->error = self::ERR_DB_ERROR;
			return FALSE;
		}
	}

	/**
	 * Duplicate this step into assignment $assignid
	 *
	 * @param integer $assignid Assignment to which this step will be added
	 * @param object $user RPC_User User to create step for, or do not store in database if null
	 * @param integer $due_date New step due_date. If NULL, the original step's date will be used
	 * @access public
	 * @return integer Unique id of the newly created duplicate step
	 */
	public function duplicate($assignid=NULL, $user=NULL, $due_date)
	{
		// Is this to be actually stored, or just temporary
		$store_step = $assignid !== NULL ? TRUE : FALSE;

		if ($store_step)
		{
			// assignid is a positive int and due_date is NULL or positive int (timestamp)
			if (!ctype_digit(strval($assignid)) || ($due_date !== NULL && !ctype_digit(strval($due_date))))
			{
				$this->error = self::ERR_INVALID_INPUT;
				return FALSE;
			}
			// The target assignment must already exist
			if (!RPC_Assignment::exists($assignid, $this->db))
			{
				$this->error = self::ERR_NO_SUCH_ASSIGNMENT;
				return FALSE;
			}

			// Input is valid, so proceed...


			$qry = sprintf(self::INSERT_QUERY,
				$assignid,
				$this->db->real_escape_string($this->title),
				$this->db->real_escape_string($this->description),
				$this->db->real_escape_string($this->teacher_description),
				$this->db->real_escape_string($this->annotation),
				$this->position,
				$due_date === NULL ? $this->due_date : $due_date,
				$this->percent
			);
			// Success, return the new step
			if ($result = $this->db->query($qry))
			{
				$new_step_id = $this->db->insert_id;
				$this->db->commit();
				return new self($new_step_id, $user, $this->config, $this->db);
			}
			else
			{
				$this->error = self::ERR_DB_ERROR;
				return FALSE;
			}
		}
		// Not storing this step...
		else
		{
			$temp_step = new self(NULL, NULL, $this->config, $this->db);
			$temp_step->position = $this->position;
			$temp_step->title = $this->title;
			$temp_step->description = $this->description;
			$temp_step->teacher_description = $this->teacher_description;
			$temp_step->annotation = $this->annotation;
			$temp_step->due_date = $due_date !== NULL ? $due_date : $this->due_date;
			// Days left for step has to be calculated since the DB doesn't provide
			$temp_step->days_left = floor(($temp_step->due_date - (time() - (time() % 86400))) / 86400);
			$temp_step->percent = $this->percent;
			return $temp_step;
		}
	}

	/**
	 * Sanitize for update
	 *
	 * @access public
	 * @return void
	 */
	public function sanitize()
	{
		// Reminder date is unix timestamp
		if (!ctype_digit(strval($this->due_date))) $this->invalid_fields['due_date'] = TRUE;
		parent::sanitize();
		return $this->_is_sanitized;
	}

	/**
	 * Save the current state of this assignment to the database
	 *
	 * @access public
	 * @return boolean
	 */
	public function update()
	{
		if (!$this->is_editable)
		{
			$this->error = self::ERR_ACCESS_DENIED;
			return FALSE;
		}
		$this->sanitize();
		if (!$this->_is_sanitized)
		{
			$this->error = self::ERR_DATA_UNSANITIZED;
			return FALSE;
		}
		$qry = sprintf(self::UPDATE_QUERY,
			// real_escape_string() and trim() already have been called by parent::sanitize()
			$this->title,
			$this->description,
			$this->teacher_description,
			$this->annotation,
			$this->due_date,
			$this->reminder_sent_date,
			$this->percent,
			$this->id
		);

		if ($result = $this->db->query($qry))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	/**
	 * Set due date and update days_left property.
	 * If reminder_date
	 *
	 * @param mixed $date
	 * @access public
	 * @return void
	 */
	public function set_due_date($date)
	{
		if (!ctype_digit(strval($date)))
		{
			$this->error = self::ERR_INVALID_INPUT;
			return FALSE;
		}
		$this->due_date = $date;
		$this->days_left = floor(($this->due_date - (time() - (time() % 86400))) / 86400);

		// If a reminder has already been sent, also reset the reminder date if the new date is in the future
		if ($this->due_date > time() && $this->reminder_sent_date !== NULL && $this->reminder_sent_date > 0)
		{
			$this->clear_notify_date();
		}
		return TRUE;
	}


	/* -------------- Static Methods --------------- */

	/**
	 * Create a new step.  If $assignid is NULL, the new step will not be stored in the database.
	 * If $assignid is a valid assignment ID, the step will be stored and attached to it.
	 * NOTE: As part of successful creation, the database transaction will be committed!
	 *       This is inconsistent with normal behavior in the application, and it should
	 *       be made consistent in the future.  TODO: Make commit behavior consistent!
	 *
	 * @param integer $assignid Unique ID of the associated assignment, or NULL if this step is not yet to be stored.
	 * @param object $user RPC_User User to create this assignment for, or do not store in database if null
	 * @param string $title Step title
	 * @param string $description Step description/content
	 * @param string $teacher_description Step description/content for teachers
	 * @param string $annotation Step annotation / notes for students
	 * @param int $position Position in assignment
	 * @param int $due_date Unix timestamp Due date/Reminder date for step
	 * @param int $percent Percentage of time spent on this step (if parent is a template)
	 * @param object $config RPC_Config configuration singleton
	 * @param object $db MySQLi database connection singleton
	 * @static
	 * @access public
	 * @return object RPC_Step or FALSE on failure
	 */
	public static function create($assignid=NULL, $user, $title="", $description="", $teacher_description="", $annotation, $position=1, $due_date=NULL, $percent=NULL, $config, $db)
	{
		// Store this assignment?
		$store = $assignid === NULL ? FALSE : TRUE;
		if ($store)
		{
			// Must be valid assignment
			if (!ctype_digit(strval($assignid)))
			{
				return FALSE;
			}

			// Position is int
			if (!ctype_digit(strval($position)))
			{
				return FALSE;
			}

			$qry = sprintf(self::INSERT_QUERY,
				$assignid,
				$db->real_escape_string($title),
				$db->real_escape_string(self::step_strip_tags($description)),
				$db->real_escape_string(self::step_strip_tags($teacher_description)),
				$db->real_escape_string(self::step_strip_tags($annotation)),
				$position,
				$due_date,
				$percent
			);
			if ($result = $db->query($qry))
			{
				$new_step_id = $db->insert_id;
				$db->commit();
				return new self($new_step_id, $user, $config, $db);
			}
			else
			{
				$db->rollback();
				return FALSE;
			}
		}
		// Not storing to database.  Just load properties to empty object.
		else
		{
			$step = new self(NULL, NULL, $db, $config);
			$step->title = $title;
			$step->description = $description;
			$step->teacher_description = $teacher_description;
			$step->annotation = "";
			$step->position = $position;
			$step->due_date = $due_date;
			$step->days_left = floor(($step->due_date - (time() - (time() % 86400))) / 86400);
			$step->percent = $percent;
			return $step;
		}
	}
}
?>
