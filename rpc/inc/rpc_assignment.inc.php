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
 * Class RPC_Assignment
 * Assignment as a complete project defined by a user
 *
 * @package RPC
 */
class RPC_Assignment extends RPC_Assignment_Base
{
	const ACTION_SET_STARTDATE = 11;
	const ACTION_SET_DUEDATE = 12;
	const ACTION_SET_NOTIFY = 13;
	const ACTION_SET_SHARED = 14;

	const ERR_CANNOT_CALCULATE_STEPDATES = 21;
	/**
	 * Date assignment begins (Unix timestamp)
	 *
	 * @var integer
	 * @access public
	 */
	public $start_date;
	/**
	 * Date assignment is due (Unix timestamp)
	 *
	 * @var int
	 * @access public
	 */
	public $due_date;
	/**
	 * Total duration of assignment in days
	 *
	 * @var int
	 * @access public
	 */
	public $days;
	/**
	 * Days remaining to complete assignment
	 *
	 * @var int
	 * @access public
	 */
	public $days_left;
	/**
	 * Should email reminders be sent for assignment milestones?
	 *
	 * @var boolean
	 * @access public
	 */
	public $send_reminders;
	/**
	 * Is this assignment shared? (Viewable by users other than $this->author)
	 *
	 * @var boolean
	 * @access public
	 */
	public $is_shared;
	/**
	 * Is this assignment temporary (created by a guest user and unsaved) ?
	 *
	 * @var boolean
	 * @access public
	 */
	public $is_temporary = TRUE;
	/**
	 * URL to create a copy of this assignment
	 * 
	 * @var string
	 * @access public
	 */
	public $url_copy;
	/**
	 * URL to create a linked assignment based on this assignment
	 * 
	 * @var mixed
	 * @access public
	 */
	public $url_link;

	/**
	 * Predefined INSERT statement for assignment creation.
	 * Params for sprintf():
	 *    %u: userid
	 *    %u: lastedit_userid
	 *    %s: title
	 *    %s: class
	 *    %u: startdate (UNIX timestamp)
	 *    %u: duedate (UNIX timestamp)
	 *    %s: parent (integer or "NULL")
	 *    %s: ancestraltemplate (integer or "NULL")
	 */
	const INSERT_QRY =
		"INSERT INTO assignments (
			userid,
			lastedit_userid,
			title,
			description,
			class,
			startdate,
			duedate,
			parent,
			ancestraltemplate
		) VALUES (%u, %u, '%s', NULL, '%s', FROM_UNIXTIME(%u), FROM_UNIXTIME(%u), %s, %s);";
	/**
	 * Predefined UPDATE statement for assignment update
	 * Params for sprintf():
	 *    %u: lastedit_userid
	 *    %s: title
	 *    %s: description
	 *    %s: class
	 *    %u: startdate (UNIX timestamp)
	 *    %u: duedate (UNIX timestamp)
	 *    %u: remind (boolean 0|1)
	 *    %u: shared (boolean 0|1)
	 *    %s: parent (integer or "NULL")
	 *    %s: ancestraltemplate (integer or "NULL")
	 *    %u: assignid
	 */
	const UPDATE_QUERY =
		"UPDATE assignments SET
			lastedit_userid = %u,
			title = '%s',
			description = '%s',
			class = '%s',
			startdate = FROM_UNIXTIME(%u),
			duedate = FROM_UNIXTIME(%u),
			remind = %u,
			shared = %u,
			parent = %s,
			ancestraltemplate = %s
		WHERE assignid = %u;";

	/**
	 * Retrieve an assignment by unique id or create an empty object if $id===NULL
	 *
	 * @param integer $id Unique ID of assignment to retrieve, or NULL
	 * @param object $user RPC_User attempting to access this assignment
	 * @param object $config RPC_Config configuration singleton
	 * @param object $db MySQLi database connection singleton
	 * @access public
	 * @return object RPC_Assignment
	 */
	public function __construct($id=NULL, $user=NULL, $config, $db)
	{
		$this->config = $config;
		$this->db = $db;

		$retrieve_assignment = $id !== NULL ? TRUE : FALSE;
		if ($retrieve_assignment)
		{
			// $id must be an unsigned integer
			if (!ctype_digit(strval($id)))
			{
				$this->error = self::ERR_INVALID_INPUT;
				return;
			}

			$qry = sprintf(<<<QRY
				SELECT
					id,
					userid,
					title,
					description,
					class,
					create_date,
					start_date,
					due_date,
					days,
					days_left,
					send_reminders,
					is_shared,
					parent,
					parent_type,
					ancestral_template
				FROM assignments_brief_vw WHERE id=%u;
QRY
				// sprintf params
				, $id);


			if ($result = $this->db->query($qry))
			{
				// Assignment not located
				if ($result->num_rows === 1)
				{
					$row = $result->fetch_assoc();

					// First get author and sharing information
					$this->_active_userid = $user->id;
					$this->id = intval($row['id']);
					$this->is_shared = $row['is_shared'] == 1 ? TRUE : FALSE;
					$this->author = intval($row['userid']);
					$this->is_temporary = FALSE;
					// Check that the user accessing has permission
					// Anyone can view if $this->is_shared (though maybe not edit)
					if (!$this->is_shared && ($user->id == NULL || (isset($user->id) && $user->id !== $this->author)))
					{
						$this->error = self::ERR_ACCESS_DENIED;
					}
					else
					{
						$this->title = $row['title'];
						$this->description = $row['description'];
						$this->class = $row['class'];
						$this->creation_date = intval($row['create_date']);
						$this->start_date = intval($row['start_date']);
						$this->due_date = intval($row['due_date']);
						$this->days = intval($row['days']);
						$this->days_left = intval($row['days_left']);
						// Reminders only valid for owning user
						$this->send_reminders = $this->author == $user->id && $row['send_reminders'] == 1 ? TRUE : FALSE;
						// Only the author can return and edit it.
						$this->is_editable = $this->author == $user->id ? TRUE : FALSE;
						$this->parent = intval($row['parent']);
						$this->parent_type = $row['parent_type'];
						$this->ancestral_template = $row['ancestral_template'];

						// Setup actions available for links
						// Author gets link to parent
						if ($this->_active_userid == $this->author && !empty($this->parent))
						{
							$this->valid_actions[] = 'parent';
						}
						if ($this->is_editable || $this->is_shared)
						{
							// Anyone who can view...
							$this->valid_actions[] = '';
						}
						if ($this->is_editable)
						{
							$this->valid_actions[] = 'edit';
							$this->valid_actions[] = 'delete';
						}
						// Actions for non-owner user
						if ($this->is_shared && $this->_active_userid != $this->author)
						{
							$this->valid_actions[] = 'link';
							$this->valid_actions[] = 'copy';
						}

						// Direct descendants of templates get basic mode for students.
						$this->default_edit_mode = $user->type == "STUDENT" && $this->parent_type == "template" ? "BASIC" : "ADVANCED";
						$result->close();

						$this->url = self::get_url($this->id, "", $this->config);
						$this->url_edit = self::get_url($this->id, "edit", $this->config);
						$this->url_delete = self::get_url($this->id, "delete", $this->config);
						$this->url_copy = self::get_url($this->id, "copy", $this->config);
						$this->url_link = self::get_url($this->id, "link", $this->config);
						// Load associated steps
						$this->get_steps($user);

					}
				}
				// Assignment ID is good, build the object
				else
				{
					$this->error = self::ERR_NO_SUCH_OBJECT;
				}
			}
			else
			{
				$this->error = self::ERR_DB_ERROR;
			}
		}
		// If no ID, so empty object returned
		return;
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
		// $start_date,$due_date unsigned int not null
		if (!ctype_digit(strval($this->start_date))) $this->invalid_fields['start_date'] = TRUE;
		if (!ctype_digit(strval($this->due_date))) $this->invalid_fields['due_date'] = TRUE;
		// parent::sanitize() will set $this->_is_sanitized
		parent::sanitize();
		return $this->_is_sanitized;
	}
	/**
	 * Store a newly created assignment for $user
	 * Will fail if $this->id and $this->author already exist,
	 * since that implies it has already been saved and should be updated
	 * instead.
	 *
	 * @param RPC_User $user
	 * @access public
	 * @return integer New assignment id
	 */
	private function _store($user)
	{
		$this->sanitize();
		if (!$this->_is_sanitized)
		{
			$this->error = self::ERR_DATA_UNSANITIZED;
			return FALSE;
		}
		// mysqli->real_escape_string() has already been called by sanitize()
		$qry = sprintf(self::INSERT_QRY,
			$user->id,
			$user->id,
			$this->title,
			$this->class,
			$this->start_date,
			$this->due_date,
			$this->parent > 0 ? $this->parent : "NULL",
			$this->ancestral_template > 0 ? $this->ancestral_template : "NULL"
		);
		if ($result = $this->db->query($qry))
		{
			$this->id = $this->db->insert_id;
			$this->author = $user->id;
			$this->_active_userid = $user->id;
			return $this->id;
		}
		else
		{
			$this->error = self::ERR_DB_ERROR;
			return FALSE;
		}
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
			$this->error = self::ERR_READONLY;
			return FALSE;
		}
		$this->sanitize();
		if (!$this->_is_sanitized)
		{
			$this->error = self::ERR_DATA_UNSANITIZED;
			return FALSE;
		}
		// mysqli->real_escape_string() already called by sanitize()
		$qry = sprintf(self::UPDATE_QUERY,
			$this->_active_userid,
			$this->title,
			$this->description,
			$this->class,
			$this->start_date,
			$this->due_date,
			$this->send_reminders ? 1 : 0,
			$this->is_shared ? 1 : 0,
			$this->parent > 0 ? $this->parent : "NULL",
			$this->ancestral_template > 0 ? $this->ancestral_template : "NULL",
			$this->id
		);

		// Update the container assignment, and if successful, also update all
		// the member steps
		if ($result = $this->db->query($qry))
		{
			foreach ($this->steps as $step)
			{
				// A failure of any one will set error and exit
				if (!$step->update())
				{
					$this->error = self::ERR_CANNOT_UPDATE_STEP;
					return FALSE;
				}
			}
			return TRUE;
		}
		else
		{
			$this->error = self::ERR_DB_ERROR;
			return FALSE;
		}
	}
	/**
	 * Set the assignment due date and update the days property
	 *
	 * @param integer $date (Unix timestamp)
	 * @access public
	 * @return boolean
	 */
	public function set_start_date($date)
	{
		if (!ctype_digit(strval($date)))
		{
			$this->error = self::ERR_INVALID_INPUT;
			return FALSE;
		}
		$this->start_date = $date;
		$this->days = floor(($this->due_date - $this->start_date)/86400);
		return TRUE;
	}
	/**
	 * Set the assignment due date and update the days and days_left properties
	 *
	 * @param integer $date (Unix timestamp)
	 * @access public
	 * @return boolean
	 */
	public function set_due_date($date)
	{
		if (!ctype_digit(strval($date)))
		{
			$this->error = self::ERR_INVALID_INPUT;
			return FALSE;
		}
		$this->due_date = $date;
		$this->days = floor(($this->due_date - $this->start_date)/86400);
		$this->days_left = floor(($this->due_date - (time() - (time() % 86400))) / 86400);
		return TRUE;
	}
	/**
	 * Calculate stepdates according to assignment start/due dates and
	 * step defined percentages
	 *
	 * @param RPC_User $user
	 * @param integer $start_date
	 * @param integer $due_date
	 * @access public
	 * @return boolean
	 */
	public function calculate_stepdates($user, $start_date, $due_date)
	{
		// NULL dates default to current object date
		$start_date = $start_date === NULL ? $this->start_date : $start_date;
		$due_date = $due_date === NULL ? $this->due_date : $due_date;

		if (!ctype_digit(strval($start_date)) || !ctype_digit(strval($due_date)))
		{
			$this->error = self::ERR_INVALID_INPUT;
			return FALSE;
		}

		// Iterating over steps
		// while rolling up the due-dates according to steps that have defined
		// interval percent.
		//
		// First thing is to read in all the steps and total up defined
		// percentages.  Then due dates for undefined percent steps will be divided
		// up among the remaining times.
		$percent_allocated = 0;
		$num_unallocated_steps = 0;
		$percent_generic = 0;
		$add_secs_generic = 0;
		// Array of position => duedate
		$arr_duedates = array();
		foreach ($this->steps as $step)
		{
			if ($step->percent > 0)
			{
				$percent_allocated += $step->percent;
			}
			else $num_unallocated_steps++;
		}
		$percent_unallocated = 100 - $percent_allocated;
		// Percent allocation for each remaining step:
		if ($percent_unallocated > 0)
		{
			if ($num_unallocated_steps > 0)
			{
				$percent_generic = floor($percent_unallocated / $num_unallocated_steps);
			}
			// Number of seconds for generic (unallocated) steps
			$add_secs_generic = floor($this->days * ($percent_generic * 0.01)) * 86400;
		}
		else
		{
			$percent_generic = 0;
			$add_secs_generic = 0;
		}
		// And create the steps while keeping track of the current duedate
		$current_start_date = $this->start_date;
		$current_due_date = NULL;
		$numsteps = count($this->steps);
		$curstep = 1;
		foreach ($this->steps as $step)
		{
			if ($step->percent > 0)
			{
				$add_secs = floor($this->days * ($step->percent * 0.01)) * 86400;
			}
			else
			{
				$add_secs = $add_secs_generic;
			}
			// The last step should end on the selected due date
			// TODO: This is obviously a bad hack in need of a better algorithm!
			$current_due_date = $curstep != $numsteps ? $current_start_date + $add_secs : $this->due_date;
			$current_start_date = $current_due_date;
			$step->due_date = $current_due_date;
			$step->days_left = floor(($step->due_date - (time() - (time() % 86400))) / 86400);

			// Store changes if user isn't null
			if ($user)
			{
				// Any update failure blocks the transaction.
				// TODO: Fix escaping issue
				// Doing a $step->update() here causes problems with real_escape_string() if the
				// step was just created.  Don't know why yet.
				if (!$result = $step->db->query(sprintf("UPDATE steps SET reminderdate=FROM_UNIXTIME(%u) WHERE stepid=%u;", $current_due_date, $step->id)))
				{
					$this->error = self::ERR_CANNOT_CALCULATE_STEPDATES;
					return FALSE;
				}
			}
			$curstep++;
		}
		// All is well
		return TRUE;
	}
	/**
	 * Return a JSON string of step due dates in the format YYYYMMDD
	 *
	 * Output JSON string contains one array 'dates' of objects
	 * $o->dates[]->id (step id)
	 * $o->dates[]->dueDate (step due date as YYYYMMDD)
	 *
	 * @access public
	 * @return string
	 */
	public function encode_json_stepdates()
	{
		$json = new stdClass();
		$json->dates = array();
		$i = 0;
		foreach ($this->steps as $step)
		{
			$json->dates[$i] = new stdClass();
			$json->dates[$i]->id = $step->id;
			$json->dates[$i]->dueDate = date('Ymd', $step->due_date);
			$i++;
		}
		return json_encode($json);
	}
	/**
	 * Parse a JSON object containing step IDs and their associated duedates
	 * and return an array of Unix timestamps indexed by step ID
	 *
	 * JSON string contains one array 'dates' of objects
	 * $o->dates[]->id (step id)
	 * $o->dates[]->dueDate (step due date as YYYYMMDD)
	 *
	 * @param string $json
	 * @static
	 * @access public
	 * @return object
	 */
	public static function parse_json_stepdates($json)
	{
		$o = json_decode($json);
		if (!$o) return FALSE;

		// Object should contain one array 'dates' of objects
		// $o->dates[]->id
		// $o->dates[]->dueDate
		if (!isset($o->dates) || !is_array($o->dates)) return FALSE;

		$step_dates = array();
		// Bail out if any ID is not a positive int or date isn't YYYYMMDD
		foreach ($o->dates as $date)
		{
			if (preg_match('/^[0-9]{8}$/', $date->dueDate) && ctype_digit(strval($date->id)))
			{
				$t = mktime(0,0,0, substr($date->dueDate,4,2), substr($date->dueDate,6,2), substr($date->dueDate,0,4));
				if ($t)
				{
					$step_dates[$date->id] = $t;
				}
				else return FALSE;
			}
			else return FALSE;
		}
		// All valid, return the whole thing.
		return $step_dates;
	}
	/**
	 * Update due dates for the steps contained in $step_dates
	 *
	 * @param array $step_dates Step ID Indexed array of Unix timestamp due_date
	 * @access public
	 * @return boolean
	 */
	public function update_step_due_dates($step_dates)
	{
		$qry = "UPDATE steps SET reminderdate = FROM_UNIXTIME(%u) WHERE stepid = %u;";
		foreach ($this->steps as $step)
		{
			if (array_key_exists($step->id, $step_dates))
			{
				$result = $this->db->query(sprintf($qry, $step_dates[$step->id], $step->id));
				// Any failure will exit
				if (!$result) return FALSE;
			}
		}
		return TRUE;
	}
	/**
	 * Store this object in $_SESSION (useful for guest assignment creation)
	 *
	 * @access public
	 * @return boolean
	 */
	public function store_to_session()
	{
		$o = clone $this;
		$o->config = NULL;
		$o->db = NULL;
		foreach ($o->steps as $step)
		{
			$step->config = NULL;
			$step->db = NULL;
		}
		$_SESSION['active_assignment_object'] = $o;
		return TRUE;
	}

	/**
	 * Retrieve RPC_Assignment from $_SESSION and return as a complete object
	 *
	 * @param object $config RPC_Config configuration singleton
	 * @param object $db MySQLi database connection singleton
	 * @static
	 * @access public
	 * @return RPC_Assignment
	 */
	public static function retrieve_from_session($config, $db)
	{
		if (!isset($_SESSION['active_assignment_object']))
		{
			return FALSE;
		}

		$assignment = $_SESSION['active_assignment_object'];
		$assignment->is_temporary = TRUE;
		$assignment->config = $config;
		$assignment->db = $db;
		foreach ($assignment->steps as $step)
		{
			$step->config = $config;
			$step->db = $db;
		}
		return $assignment;
	}
	/**
	 * Delete the assignment currently stored in $_SESSION['active_assignment_object']
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	public static function delete_from_session()
	{
		if (array_key_exists('active_assignment_object', $_SESSION))
		{
			unset($_SESSION['active_assignment_object']);
		}
		if (array_key_exists('active_assignment_usertype', $_SESSION))
		{
			unset($_SESSION['active_assignment_usertype']);
		}
		return;
	}

	/**
	 * Return an error string
	 *
	 * @access public
	 * @return void
	 */
	public function get_error()
	{
		if (!empty($this->error))
		{
			switch ($this->error)
			{
				case self::ERR_CANNOT_CALCULATE_STEPDATES: return "An error occurred while calculating step due dates.";
				default: return parent::get_error();
			}
		}
		else return "";
	}

	/* ----------------- Static Methods ------------------------ */
	/**
	 * Create a new assignment from an empty structure (one default step only)
	 * NOTE: As part of successful creation, the database transaction will be committed!
	 *       This is inconsistent with normal behavior in the application, and it should
	 *       be made consistent in the future.  TODO: Make commit behavior consistent!
	 *
	 * @param object $user RPC_User User to create this assignment for, or do not store in database if null
	 * @param string $title Assignment title
	 * @param string $class Assignment course/class name
	 * @param integer $start_date Unix timestamp. Start date for this assignment.
	 * @param integer $due_date Unix timestamp. Due date for this assignment.
	 * @param boolean $create_default_step Should one default RPC_Step be attached to this assignment?
	 * @param object $config RPC_Config configuration singleton
	 * @param object $db MySQLi database connection singleton
	 * @static
	 * @access public
	 * @return object RPC_Assignment
	 */
	public static function create_blank($user=NULL, $title, $class, $start_date, $due_date, $create_default_step=TRUE, $config, $db)
	{
		// Default step due between start and due
		$stepdate = floor(($due_date + $start_date) / 2);
		// Has been stored?
		$stored = FALSE;

		// Create unsaved assignment, which will get stored if the user is set
		$assignment = new self(NULL, NULL, $config, $db);
		$assignment->author = $user ? $user->id : NULL;
		$assignment->title = $title;
		$assignment->class = $class;
		$assignment->start_date = $start_date;
		$assignment->due_date = $due_date;
		$assignment->is_temporary = TRUE;
		// Store for user when set
		if ($user)
		{
			$db->beginTransaction();
			$assignment->_store($user);
			// On successful store(), reload the whole object so permissions get set
			if (empty($assignment->error))
			{
				$stored = TRUE;
				$db->commit();
				$assignment = new self($assignment->id, $user, $config, $db);
			}
			else
			{
				$db->rollBack();
				return $assignment;
			}

		}
		// Add a few blank starter steps
		if ($create_default_step)
		{
			$newstep_assignid = $stored ? $assignment->id : NULL;
			$newstep_user = $stored ? $user : NULL;
			$assignment->steps[] = RPC_Step::create($newstep_assignid, $newstep_user, "Your step title goes here", "Describe this step here...", "Add additional information for teachers and instructors here...", "", 1, $stepdate, NULL, $config, $db);
			$assignment->steps[] = RPC_Step::create($newstep_assignid, $newstep_user, "Turn in your assignment", "Your assignment is due today!", "Add additional information for teachers and instructors here...", "", 2, $due_date, NULL, $config, $db);
		}
		// Days left is needed if we don't have a user
		if (!$user)
		{
			$assignment->days = floor(($due_date - $start_date)/86400);
			$assignment->days_left = floor(($due_date - (time() - (time() % 86400))) / 86400);
			$assignment->valid_actions[] = 'copy';
		}
		return $assignment;
	}
	/**
	 * Create a new assignment based on an assignment template.
	 *
	 * @param RPC_Template $template Template object to clone
	 * @param object $user RPC_User User to create this assignment for, or do not store in database if null
	 * @param string $title Assignment title
	 * @param string $class Assignment course/class name
	 * @param integer $start_date Unix timestamp. Start date for this assignment.
	 * @param integer $due_date Unix timestamp. Due date for this assignment.
	 * @param object $config RPC_Config configuration singleton
	 * @param object $db MySQLi database connection singleton
	 * @static
	 * @access public
	 * @return object RPC_Assignment
	 */
	public static function clone_from_template($template, $user=NULL, $title, $class, $start_date, $due_date, $config, $db)
	{
		$stored = FALSE;
		// Empty assignment with no properties, will be returned with error status if necessary
		$new_assign = new self(NULL, NULL, $config, $db);
		if (!get_class($template) == "RPC_Template")
		{
			$new_assign->error = self::ERR_NO_SUCH_OBJECT;
		}
		if (empty($new_assign->error))
		{
			$new_assign->title = $title;
			$new_assign->class = $class;
			$new_assign->author = $user ? $user->id : NULL;
			$new_assign->start_date = $start_date;
			$new_assign->due_date = $due_date;
			$new_assign->is_temporary = TRUE;
			// Days left is needed if we don't have a user
			// These would come from the database for authenticated users
			if (!$user)
			{
				$new_assign->days = floor(($due_date - $start_date)/86400);
				$new_assign->days_left = floor(($due_date - (time() - (time() % 86400))) / 86400);
				$new_assign->valid_actions[] = 'copy';
				$new_assign->url_copy = $config->app_relative_web_path . "?action=copy";
			}

			if ($user)
			{
				$db->beginTransaction();
				$new_assign->_store($user);
				if (empty($new_assign->error))
				{
					$stored = TRUE;
					$db->commit();
					$new_assign = new self($new_assign->id, $user, $config, $db);
				}
				else
				{
					$db->rollBack();
					return FALSE;
				}
			}

			// Add all the steps without due dates
			foreach ($template->steps as $step)
			{
				$new_assign->steps[] = $step->duplicate($new_assign->id, $user, NULL);
			}
			// Set parent and ancestor
			$db->beginTransaction();
			$new_assign->parent = $template->id;
			$new_assign->ancestral_template = $template->id;
			// Then calculate and map in the due dates and log template usage
			$new_assign->calculate_stepdates($user, $new_assign->start_date, $new_assign->due_date);
			if ($user)
			{
				$template->log_usage($user->type, TRUE);
				if ($new_assign->update())
				{
					$db->commit();
				}
				else
				{
					$db->rollBack();
				}
			}
			else
			{
				// Still have to log usage if it isn't saved
				$template->log_usage("STUDENT", FALSE);
				$db->commit();
			}
		}
		// By now all actions have been done but the assignment might be in error state!
		return $new_assign;
	}
	/**
	 * Create a new assignment based on an existing assignment. Effectively,
	 * this method imports a copy of an existing assignment for $user, preserving
	 * start and due dates.
	 * Note: $user can only clone from an assignment she has read access to!
	 *
	 * @param RPC_Assignment Assignment to clone
	 * @param object $user RPC_User User to create this assignment for, or do not store in database if null
	 * @param object $config RPC_Config configuration singleton
	 * @param object $db MySQLi database connection singleton
	 * @static
	 * @access public
	 * @return object RPC_Assignment
	 */
	public static function clone_from_assignment($orig_assignment, $user=NULL, $config, $db)
	{
		// Error in $orig_assignment stops us here.  Return a blank RPC_Assignment with error
		// status set
		if (!get_class($orig_assignment) == "RPC_Assignment")
		{
			$err_assign = self::create_blank(NULL, NULL, NULL, NULL, NULL, NULL, $config, $db);
			$err_assign->error = self::ERR_NO_SUCH_OBJECT;
			return $err_assign;
		}
		else
		{
			// Create the new assignment with no steps
			// When saving a copy of an assignment from the database, add text to indicate it's a copy.
			// If saving an assignment created while not logged in, we don't need extra text.
			$title_append = $orig_assignment->id !== NULL ? " (Copy)" : "";
			$new_assign = self::create_blank($user, $orig_assignment->title . $title_append, $orig_assignment->class, $orig_assignment->start_date, $orig_assignment->due_date, FALSE, $config, $db);
			if (empty($new_assign->error))
			{
				$db->beginTransaction();
				// Set the new assignment's parent
				$new_assign->parent = $orig_assignment->id;
				// Duplicate all the original steps into the new assignment
				foreach ($orig_assignment->steps as $step)
				{
					// Copy steps between assignments, and preserve dates
					$new_assign->steps[] = $step->duplicate($new_assign->id, $user, $step->due_date);
				}
				// Save changes to the new assignment (parent set)
				if ($orig_assignment->is_temporary)
				{
					$new_assign->parent = $orig_assignment->parent;	
				}
				else $new_assign->parent = $orig_assignment->id;
				$new_assign->ancestral_template = $orig_assignment->ancestral_template;
				if ($new_assign->update())
				{
					$db->commit();
				}
				else
				{
					$db->rollBack();
				}
			}
			// May be in error state
			return $new_assign;
		}
	}
	/**
	 * Build a URL to assignment $id 
	 * 
	 * @param integer $id 
	 * @param string $type URL type ("",edit,delete,link,copy) 
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
			$action = in_array($type, array('','edit','delete','link','copy')) ? $type : "";

			$url = "";
			if ($config->app_use_url_rewrite)
			{
				$url = $config->app_fixed_web_path . "assignments/" . $id;
				if ($action !== "") $url .= "/$action";
			}
			else 
			{
				$url = $config->app_fixed_web_path . "?assign=" . $id;
				if ($action !== "") $url .= "&action=$action";
			}
			return $url;
		}
		else return "";
	}
}
?>
