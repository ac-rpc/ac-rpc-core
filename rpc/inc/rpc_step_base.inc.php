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
 * RPC_Step_Base
 * Base class for assignment and template steps
 *
 * @abstract
 * @package RPC
 */
abstract class RPC_Step_Base
{
	const ERR_INVALID_INPUT = 11;
	const ERR_NO_SUCH_STEP = 12;
	const ERR_DB_ERROR = 13;
	const ERR_DATA_UNSANITIZED = 14;
	const ERR_CANNOT_DUPLICATE_STEP = 15;
	const ERR_CANNOT_DUPLICATE_TEACHERSTEP = 16;
	const ERR_NO_SUCH_ASSIGNMENT = 17;
	const ERR_ACCESS_DENIED = 18;
	const ERR_CANNOT_DELETE_ONLY_STEP = 19;
	const ERR_CANNOT_CHANGE_ASSIGNMENT_DUEDATE = 20;

	// Step actions
	const ACTION_MOVE_UP = 1;
	const ACTION_MOVE_DOWN = 2;
	const ACTION_MOVE_TO_POS = 3;
	const ACTION_DELETE = 4;
	const ACTION_SET_TITLE = 5;
	const ACTION_SET_DESC = 6;
	const ACTION_SET_TEACHER_DESC = 7;
	const ACTION_SET_DUEDATE = 8;
	const ACTION_CREATE_STEP_AFTER = 9;
	const ACTION_CREATE_STEP_APPENDED = 10;
	const ACTION_SET_PERCENT = 11;
	const ACTION_SET_ANNOTATION = 12;

	/**
	 * Unique integer ID of this step
	 *
	 * @var integer
	 * @access public
	 */
	public $id;
	/**
	 * Unique integer ID of parent template/assignment
	 *
	 * @var integer
	 * @access public
	 */
	public $parent;
	/**
	 * Unique id of the user who owns this step
	 *
	 * @var integer
	 * @access public
	 */
	public $author;
	/**
	 * Is this object editable?
	 *
	 * @var boolean
	 * @access public
	 */
	public $is_editable;
	/**
	 * Position of step within parent template/assignment
	 *
	 * @var integer
	 * @access public
	 */
	public $position;
	/**
	 * Title (terse identifying phrase) of step
	 *
	 * @var string
	 * @access public
	 */
	public $title;
	/**
	 * Long description/content
	 *
	 * @var string
	 * @access public
	 */
	public $description;
	/**
	 * Long description/content for teachers
	 *
	 * @var string
	 * @access public
	 */
	public $teacher_description;
	/**
	 * User annotation (notes/comments) on step
	 *
	 * @var string
	 * @access public
	 */
	public $annotation;
	/**
	 * Error status
	 *
	 * @var integer
	 * @access public
	 */
	public $error = NULL;
	/**
	 * Has this object been sanitized for database update?
	 *
	 * @var boolean
	 * @access private
	 */
	protected $_is_sanitized = FALSE;
	/**
	 * Associative array of members not valid for database insertion
	 *
	 * @var array
	 * @access public
	 */
	public $invalid_fields = array();
	/**
	 * Global database connection singleton
	 *
	 * @var \PDO database connection singleton
	 * @access public
	 */
	public $db;
	/**
	 * Global RPC_Config configuration singleton
	 *
	 * @var object RPC_Config configuration singleton
	 * @access public
	 */
	public $config;

	/**
	 * Sanitize for database update and add store invalid fields
	 *
	 * @access public
	 * @return boolean
	 */
	public function sanitize()
	{
		// Title is required
		if (empty($this->title)) $this->invalid_fields['title'] = TRUE;
		else $this->title = trim(substr($this->title, 0, 512));
		// Description not required
		$this->description = trim(self::step_strip_tags($this->description));
		$this->teacher_description = trim(self::step_strip_tags($this->teacher_description));
		$this->annotation = trim(self::step_strip_tags($this->annotation));

		if (count($this->invalid_fields) > 0)
		{
			$this->_is_sanitized = FALSE;
		}
		else $this->_is_sanitized = TRUE;

		return $this->_is_sanitized;
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
				case self::ERR_INVALID_INPUT: return "Invalid input.";
				case self::ERR_NO_SUCH_STEP: return "The requested step could not be found.";
				case self::ERR_DB_ERROR: return "A database error occurred.";
				case self::ERR_DATA_UNSANITIZED: return "Input data has not been sanitized.";
				case self::ERR_CANNOT_DUPLICATE_STEP: return "The step could not be duplicated.";
				case self::ERR_NO_SUCH_ASSIGNMENT: return "The requested assignment could not be found.";
				case self::ERR_ACCESS_DENIED: return "You do not have permission to view or change this item.";
				case self::ERR_CANNOT_DELETE_ONLY_STEP: return "You cannot delete the only step.";
				case self::ERR_CANNOT_CHANGE_ASSIGNMENT_DUEDATE: return "The assignment's due date could not be reset.";
				default: return "";
			}
		}
	}
	/**
	 * Set this step's position to $position
	 * Note: This does not affec the positions of other steps in the
	 * parent assignment!
	 *
	 * @param integer $position
	 * @access public
	 * @return boolean
	 */
	public function set_position($position)
	{
		if (!$this->is_editable)
		{
			$this->error = self::ERR_ACCESS_DENIED;
			return FALSE;
		}
		if (!ctype_digit(strval($position)))
		{
			$this->error = self::ERR_INVALID_INPUT;
		}
		$qry = "UPDATE steps SET position = :position WHERE stepid = :stepid";
		$stmt = $this->db->prepare($qry);
		if ($stmt->execute(array(':position' => $position, ':stepid' => $this->id)))
		{
			$this->position = $position;
			return TRUE;
		}
		else
		{
			$this->error = self::ERR_DB_ERROR;
			return FALSE;
		}
	}
	/**
	 * Call strip_tags() on $string using a set of allowable tags.
	 * This is mainly intended for step descriptions.
	 *
	 * @param string $string
	 * @static
	 * @access public
	 * @return string
	 */
	public static function step_strip_tags($string)
	{
		// But only some HTML tags are permitted.
		$arr_tags_allowed = array(
			"<h1>","<h2>","<h3>","<h4>","<h5>",
			"<font>",
			"<a>",
			"<p>",
			"<br>",
			"<b>","<i>","<strong>","<em>","<underline>","<strikethrough>",
			"<sub>","<sup>","<blockquote>","<pre>","<code>",
			"<ol>","<ul>","<li>",
			"<img>"
		);
		$stripped = strip_tags($string, implode("", $arr_tags_allowed));
		return preg_replace_callback('/<(.*?)>/i', function($m) {
      return "<" . self::step_strip_tag_attributes($m[1]) . ">";
    }, $stripped);
	}
	/**
	 * Disable unwanted HTML attributes from within tags
	 * Adapted from comments in the PHP strip_tags() manual
	 * TODO: Really remove the attributes completely instead of defanging them as "forbidden"
	 *
	 * @param string $string
	 * @static
	 * @access private
	 * @return string
	 */
	protected static function step_strip_tag_attributes($string)
	{
		$arr_disallowed_attrs = array(
			"javascript",
			"onmouseover",
			"onmouseout",
			"onclick",
			"ondblclick",
			"onmousedown",
			"onmouseuop",
			"onmousemove",
			"onkeypress",
			"onkeydown",
			"onkeyup",
			"style",
			"class"
		);
		return stripslashes(preg_replace("/" . implode("|", $arr_disallowed_attrs) . "/i", "badattr", $string));
	}
}
