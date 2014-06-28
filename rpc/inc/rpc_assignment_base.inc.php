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
 * Class RPC_Assignment_Base
 * Base class for assignment and template objects
 *
 * @package RPC
 */
abstract class RPC_Assignment_Base
{
	const ERR_NO_SUCH_OBJECT = 11;
	const ERR_INVALID_INPUT = 12;
	const ERR_ACCESS_DENIED = 13;
	const ERR_DB_ERROR = 14;
	const ERR_DATA_UNSANITIZED = 15;
	const ERR_CANNOT_DUPLICATE_STEP = 16;
	const ERR_CANNOT_MOVE_STEP = 17;
	const ERR_READONLY = 18;
	const ERR_CANNOT_DUPLICATE_TEMPLATE = 19;

	const ACTION_CLONE = 1;
	const ACTION_DELETE = 2;
	const ACTION_SET_TITLE = 3;
	const ACTION_SET_DESC = 4;
	const ACTION_CLEAR = 5;
	const ACTION_LINK = 6;

	/**
	 * Unique identifier for this project
	 *
	 * @var integer
	 * @access public
	 */
	public $id;
	/**
	 * Array of RPC_Step step objects bound to this assignment
	 *
	 * @var array
	 * @access public
	 */
	public $steps = array();
	/**
	 * Unique identifier of the user who authored this assignment
	 *
	 * @var integer
	 * @access public
	 */
	public $author;
	/**
	 * User id of user currently accessing
	 *
	 * @var integer
	 * @access private
	 */
	private $_active_userid;
	/**
	 * Assignment title, a terse identifying phrase
	 *
	 * @var string
	 * @access public
	 */
	public $title;
	/**
	 * Verbose assignment description
	 *
	 * @var string
	 * @access public
	 */
	public $description;
	/**
	 * Class/course associated with this assignment
	 *
	 * @var string
	 * @access public
	 */
	public $class;
	/**
	 * Date assignment was created (Unix timestamp)
	 *
	 * @var integer
	 * @access public
	 */
	public $creation_date;

	/**
	 * Does the active user have permission to edit this object?
	 *
	 * @var boolean
	 * @access public
	 */
	public $is_editable = FALSE;
	/**
	 * Unique RPC_Assignment->id of assignment from which this one is derived, if any.
	 *
	 * @var integer
	 * @access public
	 */
	public $parent;
	/**
	 * Unique RPC_Template->id of original template used to create object.
	 * If an assignment is cloned from another assignment, which itself was originally
	 * cloned from a template, that original template id persists here.
	 *
	 * @var integer
	 * @access public
	 */
	public $ancestral_template;
	/**
	 * Object type of parent (assignment|template)
	 *
	 * @var string
	 * @access public
	 */
	public $parent_type;
	/**
	 * Default editing mode:
	 * BASIC: Most step manipulation controls hidden
	 * ADVANCED: All step manipulation controls visible
	 * 
	 * @var string
	 * @access public
	 */
	public $default_edit_mode = "BASIC";

	/**
	 * Have the properties been sanitized for database insert/update?
	 *
	 * @var boolean
	 * @access protected
	 */
	protected $_is_sanitized = FALSE;
	/**
	 * Array of currently invalid properties which must
	 * be corrected before database insertion/update can proceed.
	 *
	 * @var array
	 * @access public
	 */
	public $invalid_fields = array();
	/**
	 * Error status
	 *
	 * @var integer
	 * @access public
	 */
	public $error = NULL;
	/**
	 * Valid actions for this object type
	 *
	 * @var array
	 * @static
	 * @access public
	 */
	public $valid_actions = array();
	/**
	 * HTTP link to this assignment, based on whether or not URL rewriting is being used
	 *
	 * @var string
	 * @access public
	 */
	public $url;
	/**
	 * HTTP link to this assignment in edit mode, based on whether or not URL rewriting is being used
	 *
	 * @var string
	 * @access public
	 */
	public $url_edit;
	/**
	 * HTTP link to this assignment's delete action, based on whether or not URL rewriting is being used
	 *
	 * @var string
	 * @access public
	 */
	public $url_delete;
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
	 * Load RPC_Step objects for all associated steps to $this->steps
	 *
	 * @access public
	 * @return boolean
	 */
	public function get_steps($user)
	{
		// Initialize in case we're reloading...
		$this->steps = array();
		$qry = sprintf("SELECT stepid, position FROM steps WHERE assignid=%u ORDER BY position, reminderdate;", $this->id);
		if ($result = $this->db->query($qry))
		{
			$rows = $result->fetchAll();
			foreach ($rows as $row)
			{
				$this->steps[$row['stepid']] = new RPC_Step($row['stepid'], $user, $this->config, $this->db);
			}
			$result->closeCursor();
			return TRUE;
		}
		else
		{
			$this->error = self::ERR_DB_ERROR;
			return FALSE;
		}
	}
	/**
	 * Delete the current assignment.  Will cause a cascade delete
	 * of all associated assignment steps.
	 *
	 * @access public
	 * @return boolean
	 */
	public function delete()
	{
		if (!$this->is_editable)
		{
			$this->error = self::ERR_READONLY;
			return FALSE;
		}
		$qry = sprintf("DELETE FROM assignments WHERE assignid=%u;", $this->id);
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
	 * Returns step positions to a contiguous block from 1 to n.  Call this
	 * method after adding new or deleting steps, or moving step positions.
	 *
	 * @access public
	 * @return boolean
	 */
	public function normalize_steps()
	{
		// Query step positions, forcing ascending order
		$qry = sprintf("SELECT stepid FROM steps WHERE assignid = %u ORDER BY position ASC, reminderdate ASC;", $this->id);
		if ($result = $this->db->query($qry))
		{
			$arr_raw_steps = array();
			$rows = $result->fetchAll();
			foreach ($rows as $row)
			{
				$arr_raw_steps[] = $row;
			}
			$result->closeCursor();

			// Since the query result is already ordered, iterate over and
			// update all the positions starting with 1
			for ($i = 1; $i <= count($arr_raw_steps); $i++)
			{
				$upd_qry = sprintf("UPDATE steps SET position = %u WHERE stepid = %u;", $i, $arr_raw_steps[$i-1]['stepid']);
				if (!$upd_result = $this->db->query($upd_qry))
				{
					// Any failure, bail out.
					$this->error = self::ERR_DB_ERROR;
					return FALSE;
				}
			}
		}
		else
		{
			$this->error = self::ERR_DB_ERROR;
			return FALSE;
		}
		return TRUE;
	}
	/**
	 * Sort $this->steps into ascending order by position.  Must be normalized with $this->normalize_steps()
	 *
	 * @access public
	 * @return void
	 */
	public function sort_steps()
	{
		$a = array();
		foreach ($this->steps as $step)
		{
			$a[$step->position] = $step;
		}
		$this->steps = ksort($a);
		return;
	}
	/**
	 * Place $step at position $new_position
	 * All other steps in this assignment will be rearranged as well.
	 * Note: $this->get_steps may need to be called afterward to re-sync the steps.
	 *
	 * @param integer $stepid
	 * @param integer $new_position
	 * @param array $dates Optional array of new step reminder dates, the output of self::parse_json_stepdates()
	 *        If omitted, step reminder dates will not be modified.
	 * @access public
	 * @return boolean
	 */
	public function move_step($stepid, $new_position, $dates=NULL)
	{
		if (!$this->is_editable)
		{
			$this->error = self::ERR_READONLY;
			return FALSE;
		}
		// Has to be non-zero, positive int
		if (!ctype_digit(strval($new_position)) || $new_position == 0)
		{
			$this->error = self::ERR_INVALID_INPUT;
			return FALSE;
		}

		// These are done with a single inline query instead of iterating over $this->steps
		// Haven't done profiling, but that would result in 2n queries when 2 would do fine.

		// If position went up, decrease positions at or below.
		$old_position = $this->steps[$stepid]->position;
		if ($new_position > $old_position)
		{
			// Shift them down one place
			$new_pos_query = "UPDATE steps SET position = position - 1 WHERE assignid = %u AND stepid <> %u AND position <= %u;";
		}
		// If positon went down, increase positions at or above.
		else if ($new_position < $old_position)
		{
			// Shift them up one place
			$new_pos_query = "UPDATE steps SET position = position + 1 WHERE assignid = %u AND stepid <> %u AND position >= %u;";
		}
		// No position change, just return TRUE
		else
		{
			return TRUE;
		}

		if (!$upd_result = $this->db->query(sprintf($new_pos_query, $this->id, $stepid, $new_position)))
		{
			$this->error = self::ERR_DB_ERROR;
			return FALSE;
		}
		// Increment/decrement positions in the local copy
		foreach ($this->steps as $cstep)
		{
			if ($new_position > $old_position)
			{
				$cstep->position--;
			}
			if ($new_position < $old_position)
			{
				$cstep->position++;
			}
		}

		// Finally, modify the requested step.
		if (!$this->steps[$stepid]->set_position($new_position))
		{
			$this->error = self::ERR_CANNOT_MOVE_STEP;
			return FALSE;
		}

		// If requested, update all the other step dates
		if (is_array($dates))
		{
			return $this->update_step_due_dates($dates);
		}
		// All is well.
		return TRUE;
	}
	/**
	 * Shift all steps up one position, beginning with $starting_position
	 * Note: You should call $this->normalize_steps() afterward.
	 * Note: You may also need to call $this->get_steps to re-sync
	 *
	 * @param int $starting_position
	 * @access public
	 * @return boolean
	 */
	public function shift_steps_up($starting_position)
	{
		foreach ($this->steps as $step)
		{
			// Find the first step greater or equal to $starting_position,
			// shift it one place up which will cascade all above it up one position
			if ($step->position >= $starting_position)
			{
				return $this->move_step($step->id, $step->position + 1);
			}
		}
		// No steps at or above $starting_position so just return TRUE
		return TRUE;
	}
	/**
	 * Sanitize properties in preparation for database insert/update
	 * Must be called before self->update()
	 *
	 * @access public
	 * @return boolean
	 */
	public function sanitize()
	{
		// $author: unsigned int not null
		if (!ctype_digit(strval($this->author))) $this->invalid_fields['author'] = TRUE;
		// $parent: unsigned int null
		if (!empty($this->parent))
		{
			if (!ctype_digit(strval($this->parent))) $this->invalid_fields['parent'] = TRUE;
		}
		if (!empty($this->ancestral_template))
		{
			if (!ctype_digit(strval($this->ancestral_template))) $this->invalid_fields['ancestral_template'] = TRUE;
		}
		// $title: string(512) not null
		if (empty($this->title)) $this->invalid_fields['title'] = TRUE;
		else $this->title = trim($this->db->real_escape_string(substr($this->title,0,512)));
		// $class: string(128) null
		$this->class = trim($this->db->real_escape_string(substr($this->class,0,128)));
		// $description: string null
		$this->description = trim($this->db->real_escape_string($this->description));

		// Anything invalid, return FALSE
		if (count($this->invalid_fields) > 0)
		{
			$this->_is_sanitized = FALSE;
			return FALSE;
		}
		else
		{
			$this->_is_sanitized = TRUE;
			return TRUE;
		}
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
				case self::ERR_NO_SUCH_OBJECT: return "The requested assignment or template could not be located. It may have been removed by its owner.";
				case self::ERR_INVALID_INPUT: return "Invalid input was supplied.";
				case self::ERR_ACCESS_DENIED: return "You are not allowed to view the requested resource.";
				case self::ERR_DB_ERROR: return "A database error occurred.";
				case self::ERR_DATA_UNSANITIZED: return "Object properties have not been sanitized for database insert/update.";
				case self::ERR_CANNOT_DUPLICATE_STEP: return "Step could not be duplicated.";
				case self::ERR_CANNOT_MOVE_STEP: return "Step could not be moved.";
				case self::ERR_READONLY: return "You are not allowed to edit this resource.";
				case self::ERR_CANNOT_DUPLICATE_TEMPLATE: return "The requested template could not be copied.";
				default: return "Undefined error ({$this->error})";
			}
		}
		else return "";
	}

	/* ---------- Static Methods --------------- */
	/**
	 * Does assignment/template $id exist?
	 *
	 * @param int $id Assignment unique id to test
	 * @param object $db MySQLi database connection singleton
	 * @static
	 * @access public
	 * @return boolean
	 */
	public static function exists($id, $db)
	{
		if (!ctype_digit(strval($id))) return FALSE;
		$assign_qry = "SELECT assignid FROM assignments WHERE assignid=$id";
		if ($result = $db->query($assign_qry))
		{
			if ($result->num_rows < 1)
			{
				$result->closeCursor();
				return FALSE;
			}
			else
			{
				$result->closeCursor();
				return TRUE;
			}
		}
		else return FALSE;
	}
	/**
	 * Validate a start date in the format YYYYMMDD or YYYY-MM-DD
	 * Dates are received without a time component and always set to time=00:00:00
	 *
	 * @param string $startdate
	 * @static
	 * @access public
	 * @return integer Unix timestamp
	 */
	public static function validate_startdate($startdate)
	{
		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $startdate) || preg_match('/^\d{8}$/', $startdate))
		{
			// If in hyphenated format, strip hyphens and treat as YYYYMMDD.
			if (preg_match('/^\d{4}-\d{2}-\d{2}/', $startdate)) $startdate = str_replace("-", "", $startdate);
			$newdate = mktime(0,0,0, intval(substr($startdate,4,2)), intval(substr($startdate,6,2)), intval(substr($startdate,0,4)));
			if ($newdate) return $newdate;
			else return FALSE;
		}
		else return FALSE;
	}
	/**
	 * Validate a due date in the format YYYYMMDD or YYYY-MM-DD
	 * Dates are received without a time component and always advanced to time=23:59:59
	 *
	 * @param string $duedate
	 * @static
	 * @access public
	 * @return integer Unix timestamp
	 */
	public static function validate_duedate($duedate)
	{
		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $duedate) || preg_match('/\d{8}$/', $duedate))
		{
			// If in hyphenated format, strip hyphens and treat as YYYYMMDD.
			if (preg_match('/^\d{4}-\d{2}-\d{2}/', $duedate)) $duedate = str_replace("-", "", $duedate);
			$newdate = mktime(0,0,0, intval(substr($duedate,4,2)), intval(substr($duedate,6,2)), intval(substr($duedate,0,4)));
			if ($newdate) return $newdate;
			else return FALSE;
		}
		else return FALSE;
	}
}
?>
