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
 * Class RPC_Template
 * Assignment as a complete project defined by a user
 *
 * @package RPC
 */
class RPC_Template extends RPC_Assignment_Base
{
	// Template action constants should all begin above 20!
	const ACTION_SET_PUBLISHED = 21;

	/**
	 * Name of the template's original author
	 *
	 * @var string
	 * @access public
	 */
	public $author_name;
	/**
	 * User id of most recent editor
	 *
	 * @var integer
	 * @access public
	 */
	public $lastedit_userid;
	/**
	 * Name of the template's most recent editor
	 *
	 * @var string
	 * @access public
	 */
	public $lastedit_name;
	/**
	 * Has this template been published?
	 *
	 * @var boolean
	 * @access public
	 */
	public $is_published;
	/**
	 * Predefined INSERT statement for assignment creation.
	 */
	const INSERT_QRY =
		"INSERT INTO assignments (
			userid,
			lastedit_userid,
			title,
			description,
			class,
			template,
			parent,
			ancestraltemplate
		) VALUES (:userid, :lastedit_userid, :title, NULL, :class, 1, :parent, :ancestraltemplate);";
	/**
	 * Predefined UPDATE statement for assignment update
	 */
	const UPDATE_QUERY =
		"UPDATE assignments SET
			lastedit_userid = :lastedit_userid,
			title = :title,
			description = :description,
			class = :class,
			published = :published,
			parent = :parent,
			ancestraltemplate = :ancestraltemplate
		WHERE assignid = :assignid";

	/**
	 * Retrieve an assignment template by unique id or create an empty object if $id===NULL
	 *
	 * @param integer $id Unique ID of template to retrieve, or NULL
	 * @param object $user RPC_User attempting to access this template
	 * @param object $config RPC_Config configuration singleton
	 * @param object $db MySQLi database connection singleton
	 * @access public
	 * @return object RPC_Template
	 */
	public function __construct($id=NULL, $user=NULL, $config, $db)
	{
		$this->config = $config;
		$this->db = $db;

		$retrieve_template = $id !== NULL ? TRUE : FALSE;
		if ($retrieve_template)
		{
			// $id must be an unsigned integer
			if (!ctype_digit(strval($id)))
			{
				$this->error = self::ERR_INVALID_INPUT;
				return;
			}

			$qry = <<<QRY
				SELECT
					id,
					userid,
					author,
					lastedit_userid,
					lastedit_name,
					title,
					description,
					class,
					create_date,
					is_published,
					parent,
					parent_type,
					ancestral_template
				FROM templates_vw
				WHERE id = :id
				LIMIT 1
QRY;
			$stmt = $this->db->prepare($qry);

			if ($stmt->execute(array(':id' => $id)))
			{
				// Assignment not located
				if ($row = $stmt->fetch())
				{
					// First get author and sharing information
					$this->_active_userid = $user->id;
					$this->id = intval($row['id']);
					$this->author = intval($row['userid']);
					$this->author_name = $row['author'];
					$this->lastedit_userid = $row['lastedit_userid'];
					$this->lastedit_name = $row['lastedit_name'];

					$this->title = $row['title'];
					$this->description = $row['description'];
					$this->class = $row['class'];
					$this->creation_date = intval($row['create_date']);
					$this->is_published = $row['is_published'] == 1 ? TRUE : FALSE;
					// Administrators may edit any template
					// Publishers may edit only their own templates
					if ($user->is_administrator || $user->is_superuser)
					{
						$this->is_editable = TRUE;
					}
					else if ($user->is_publisher && $this->author == $user->id)
					{
						$this->is_editable = TRUE;
					}
					else
					{
						$this->is_editable = FALSE;
					}

					// Setup actions for links
					// Everyone can view
					$this->valid_actions[] = '';
					if ($this->is_editable)
					{
						$this->valid_actions[] = 'edit';
						$this->valid_actions[] = 'delete';
					}
					$this->parent = intval($row['parent']);
					$this->parent_type = $row['parent_type'];
					$this->ancestral_template = $row['ancestral_template'];
					// Templates always get advanced editing
					$this->default_edit_mode = "ADVANCED";
					$stmt->closeCursor();

					$this->url = self::get_url($this->id, "", $this->config);
					$this->url_edit = self::get_url($this->id, "edit", $this->config);
					$this->url_delete = self::get_url($this->id, "delete", $this->config);
					// Load associated steps
					$this->get_steps($user);
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
	 * Store newly created template to database
	 *
	 * @param RPC_User $user
	 * @access private
	 * @return void
	 */
	private function _store($user)
	{
		$this->sanitize();
		if (!$this->_is_sanitized)
		{
			$this->error = self::ERR_DATA_UNSANITIZED;
			return FALSE;
		}
		$stmt = $this->db->prepare(self::INSERT_QRY);
		$stmt->bindValue(':userid', $user->id, \PDO::PARAM_INT);
		$stmt->bindValue(':lastedit_userid', $user->id, \PDO::PARAM_INT);
		$stmt->bindValue(':title', $this->title, \PDO::PARAM_STR);
		$stmt->bindValue(':class', $this->class, \PDO::PARAM_STR);
		$stmt->bindValue(':parent', ($this->parent > 0 ? $this->parent : NULL), \PDO::PARAM_INT);
		$stmt->bindValue(':ancestraltemplate', ($this->ancestral_template > 0 ? $this->ancestral_template : NULL), \PDO::PARAM_INT);

		if ($stmt->execute())
		{
			$this->id = $this->db->lastInsertId();
			$this->author = $user->id;
			$this->author_name = $user->name;
			$this->lastedit_userid = $user->id;
			$this->lastedit_name = $user->name;
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
	 * Sanitize properties in preparation for database insert/update
	 * Must be called before self->update()
	 *
	 * @access public
	 * @return boolean
	 */
	public function sanitize()
	{
		// parent::sanitize() will set $this->_is_sanitized
		parent::sanitize();
		return $this->_is_sanitized;
	}
	/**
	 * Save the current state of this template to the database
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
		$stmt = $this->db->prepare(self::UPDATE_QUERY);
		$stmt->bindValue(':lastedit_userid', $this->_active_userid, \PDO::PARAM_INT);
		$stmt->bindValue(':title', $this->title, \PDO::PARAM_STR);
		$stmt->bindValue(':description', $this->description, \PDO::PARAM_STR);
		$stmt->bindValue(':class', $this->class, \PDO::PARAM_STR);
		$stmt->bindValue(':published', ($this->is_published ? 1 : 0), \PDO::PARAM_INT);
		$stmt->bindValue(':parent', ($this->parent > 0 ? $this->parent : NULL), \PDO::PARAM_INT);
		$stmt->bindValue(':ancestraltemplate', ($this->ancestral_template > 0 ? $this->ancestral_template : NULL), \PDO::PARAM_INT);
		$stmt->bindValue(':assignid', $this->id, \PDO::PARAM_INT);

		// Update the container assignment, and if successful, also update all
		// the member steps
		if ($stmt->execute())
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
	 * Log a usage instance for this template
	 * 
	 * @param string $user_type ('STUDENT'|'TEACHER')
	 * @parent boolean is_saved
	 * @access public
	 * @return boolean
	 */
	public function log_usage($user_type, $is_saved)
	{
		if (!($user_type == "STUDENT" || $user_type == "TEACHER"))
		{
			$this->error = self::ERR_INVALID_INPUT;
			return FALSE;
		}
		$saved = $is_saved ? 1 : 0;
		$qry = "INSERT INTO template_usage (assignid, usertype, saved) VALUES (:assignid, :usertype, :saved)";
		$stmt = $this->db->prepare($qry);
		if ($stmt->execute(array(':assignid' => $this->id, ':usertype' => $user_type, ':saved' => $saved)))
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
				default: return parent::get_error();
			}
		}
		else return "";
	}

	/* ----------------- Static Methods ------------------------ */
	/**
	 * Create a new template from an empty structure (two default steps only)
	 *
	 * @param object $user RPC_User User to create this template for, or do not store in database if null
	 * @param string $title Template title
	 * @param string $class Template class
	 * @param boolean $create_default_step Should one default RPC_Step be attached to this template?
	 * @param object $config RPC_Config configuration singleton
	 * @param object $db MySQLi database connection singleton
	 * @static
	 * @access public
	 * @return object RPC_Template
	 */
	public static function create_blank($user=NULL, $title, $class, $create_default_step=TRUE, $config, $db)
	{
		// Must be admin or publisher
		if (!$user || !($user->is_administrator || $user->is_publisher))
		{
			$err_template = new self(NULL, NULL, $config, $db);
			$err_template->error = self::ERR_ACCESS_DENIED;
			return $err_template;
		}
		// Saved assignment for $user
		// Insert and return...
		else
		{
			$db->beginTransaction();
			$stored = FALSE;
			$template = new self(NULL, NULL, $config, $db);
			$template->author = $user ? $user->id : NULL;
			$template->title = $title;
			$template->class = $class;
			$template->_store($user);

			if (empty($template->error))
			{
				$stored = TRUE;
				$db->commit();
				// Rebuild the complete object
				$template = new self($template->id, $user, $config, $db);
			}
			else
			{
				$db->rollBack();
				return $template;
			}
			if (empty($template->error))
			{
				// Add blank steps
				if ($create_default_step)
				{
					// Two default steps created at 50% each
					$template->steps[] = RPC_Step::create($template->id, $user, "[Step Title...]", "Describe step here...", NULL, NULL, 1, NULL, 50,  $config, $db);
					$template->steps[] = RPC_Step::create($template->id, $user, "Turn in your assignment", "Your assignment is due today!", NULL, NULL, 2, NULL, 50, $config, $db);
				}
			}
		}
		return $template;
	}
	/**
	 * Create a new template based on an existing template.
	 *
	 * @param RPC_Template Template to clone
	 * @param object $user RPC_User User to create this template for
	 * @param string $title
	 * @param string $class
	 * @param object $config RPC_Config configuration singleton
	 * @param object $db MySQLi database connection singleton
	 * @static
	 * @access public
	 * @return object RPC_Assignment
	 */
	public static function clone_from_template($orig_template, $user=NULL, $title, $class, $config, $db)
	{
		// Must be admin or publisher
		if (!$user || !($user->is_administrator || $user->is_publisher))
		{
			$err_template = new self(NULL, NULL, $config, $db);
			$err_template->error = self::ERR_ACCESS_DENIED;
			return $err_template;
		}
		// Error in $orig_template stops us here.  Return a blank RPC_Template with error
		// status set
		if (!get_class($orig_template) == "RPC_Template")
		{
			$err_template = new self(NULL, NULL, $config, $db);
			$err_template->error = self::ERR_NO_SUCH_OBJECT;
			return $err_template;
		}
		else
		{
			// Create the new assignment with no steps
			$new_template = self::create_blank($user, $title, $class, FALSE, $config, $db);
			if (empty($new_template->error))
			{
				$db->beginTransaction();
				// Duplicate all the original steps into the new assignment
				foreach ($orig_template->steps as $step)
				{
					// Copy steps between assignments, and preserve dates
					$new_template->steps[] = $step->duplicate($new_template->id, $user, TRUE);
				}
				// Set the new assignment's parent
				$new_template->parent = $orig_template->id;
				$new_template->ancestral_template = $orig_template->id;
				if ($new_template->update())
				{
					$db->commit();
				}
				else
				{
			 		$db->rollBack();
				}
			}
			return $new_template;
		}
	}
	/**
	 * Build a URL to template $id 
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
				$url = $config->app_fixed_web_path . "templates/" . $id;
				if ($action !== "") $url .= "/$action";
			}
			else 
			{
				$url = $config->app_fixed_web_path . "?tmpl=" . $id;
				if ($action !== "") $url .= "&action=$action";
			}
			return $url;
		}
		else return "";
	}
}
