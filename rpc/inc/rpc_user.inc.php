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
 * Class RPC_User
 * Local user object
 * 
 * @package RPC
 */
class RPC_User
{
	/**
	 * System unique identifier
	 * 
	 * @var integer
	 * @access public
	 */
	public $id;
	/**
	 * Username as supplied by authentication mechanism
	 * 
	 * @var string
	 * @access public
	 */
	public $username;
	/**
	 * User-supplied name for display
	 * 
	 * @var string
	 * @access public
	 */
	public $name;
	/**
	 * User-supplied preferred email address
	 * 
	 * @var string
	 * @access public
	 */
	public $email;
	/**
	 * User type (STUDENT|TEACHER)
	 * 
	 * @var string
	 * @access public
	 */
	public $type = "STUDENT";
	/**
	 * User is permitted to access own stuff.  Basic userlevel
	 * 
	 * @var boolean
	 * @access public
	 */
	public $is_user = FALSE;
	/**
	 * User is permitted to publish templates, and edit/delete templates he/she published
	 * 
	 * @var boolean
	 * @access public
	 */
	public $is_publisher = FALSE;
	/**
	 * User is a system administrator, able to grant permissions, publish or edit ANY template
	 * 
	 * @var boolean
	 * @access public
	 */
	public $is_administrator = FALSE;
	/**
	 * User is defined in config.inc.php as a superuser.  Account cannot be deleted,
	 * and permissions cannot be revoked by administrators.
	 * 
	 * @var boolean
	 * @access public
	 */
	public $is_superuser = FALSE;
	/**
	 * Raw permission value as an integer representation
	 * 1 = User
	 * 2 = Publisher
	 * 4 = Administrator
	 * 
	 * @var integer
	 * @access public
	 */
	public $raw_perms_int;

	/**
	 * Global configuration singleton
	 * 
	 * @var object RPC_Config;
	 * @access public
	 */
	public $config = NULL;
	/**
	 * Global database connection singleton
	 * 
	 * @var object RPC_DB;
	 * @access public
	 */
	public $db = NULL;
	/**
	 * Global singleton for actively logged in user
	 * Needed to grant permissions to user objects
	 *
	 * Can only be set if self::$active_authority_user->id does NOT match $this->id
	 * 
	 * @static
	 * @var object RPC_User currently logged in user
	 * @access private
	 */
	private static $active_authority_user = NULL;
	/**
	 * Error status
	 * 
	 * @var integer
	 * @access public
	 */
	public $error = NULL;

	/*
	 * RPC_User permission specs
	 */
	const RPC_AUTHLEVEL_GUEST = 0;
	const RPC_AUTHLEVEL_USER = 1;
	const RPC_AUTHLEVEL_PUBLISHER = 2;
	const RPC_AUTHLEVEL_ADMINISTRATOR = 4;
	const RPC_AUTHLEVEL_SUPERUSER = 8;

	/**
	 * User error conditions
	 */
	const ERR_ACCESS_DENIED = 11;
	const ERR_NO_SUCH_USER = 12;
	const ERR_USER_ALREADY_EXISTS = 13;
	const ERR_CANNOT_SET_AUTHORITY = 14;
	const ERR_INVALID_AUTHLEVEL = 15;
	const ERR_DB_ERROR = 16;
	const ERR_INVALID_INPUT = 17;
	const ERR_INVALID_TYPE = 18;
	const ERR_CANNOT_DELETE_SUPERUSER = 19;

	/**
	 * Constants for static "exists" queries
	 */
	const RPC_EXISTS = 1;
	const RPC_NOT_EXISTS = 0;
	const RPC_EXISTS_ERROR = -1;

	/**
	 * Constants for user querying
	 */
	const RPC_QUERY_USER_BY_ID = 0;
	const RPC_QUERY_USER_BY_USERNAME = 1;
	const RPC_DO_NOT_QUERY = 2;

	/**
	 * Constants for querying assignment status
	 */
	const RPC_ASSIGNMENT_STATUS_ALL = 0;
	const RPC_ASSIGNMENT_STATUS_ACTIVE = 1;
	const RPC_ASSIGNMENT_STATUS_INACTIVE = 2;
	const RPC_ASSIGNMENT_STATUS_EXPIRED = 3;

	/**
	 * Constructor retrieves user object for $query ID or username 
	 * 
	 * @param integer $query_type RPC_QUERY_USER_BY_ID | RPC_QUERY_USER_BY_USERNAME
	 * @param string $query User to retrieve by username or ID
	 * @param object $config RPC_Config configuration singleton
	 * @param object $db RPC_DB MySQLi database connection singleton
	 * @access public
	 * @return RPC_User
	 */
	public function __construct($query_type=self::RPC_QUERY_USER_BY_USERNAME, $query, $config, $db)
	{
		$this->config = $config;
		$this->db = $db;

		// Querying user by username
		if ($query_type == self::RPC_QUERY_USER_BY_USERNAME)
		{
			$dbuserquery = $this->db->real_escape_string(strtoupper($query));
			$qry = sprintf("SELECT userid, username, email, name, usertype, perms FROM users WHERE UPPER(username)='%s';", $dbuserquery);
		}
		// Querying user by ID
		else if ($query_type == self::RPC_QUERY_USER_BY_ID)
		{
			if (!ctype_digit(strval($query)))
			{
				$this->error = self::ERR_INVALID_INPUT;
				return;
			}
			$qry = sprintf("SELECT userid, username, email, name, usertype, perms FROM users WHERE userid=%u;", $query);
		}
		// Just return an empty object, useful where guest authorization is OK
		else if ($query_type == self::RPC_DO_NOT_QUERY)
		{
			return;
		}
		else
		{
			$this->error = self::ERR_INVALID_QUERY_TYPE;
			return;
		}
		if ($result = $this->db->query($qry))
		{
			// Got results, load object
			if ($row = $result->fetch_assoc())
			{
				$this->id = intval($row['userid']);
				$this->username = htmlentities($row['username'], ENT_QUOTES);
				$this->name = !empty($row['name']) ? htmlentities($row['name'], ENT_QUOTES) : htmlentities($row['username'], ENT_QUOTES);
				$this->email = htmlentities($row['email'], ENT_QUOTES);
				$this->type = $row['usertype'];
				$this->raw_perms_int = intval($row['perms']);
				// Check user, publisher, and admin bits
				$this->is_user = $this->raw_perms_int & self::RPC_AUTHLEVEL_USER ? TRUE : FALSE;
				$this->is_publisher = $this->raw_perms_int & self::RPC_AUTHLEVEL_PUBLISHER ? TRUE : FALSE;
				$this->is_administrator = $this->raw_perms_int & self::RPC_AUTHLEVEL_ADMINISTRATOR ? TRUE : FALSE;

				// Check if this user is a config.inc.php defined superuser
				// Presently, the superusers have no representation in the database perms.  They are strictly
				// defined in config.inc.php!
				if (in_array($this->username, $this->config->auth_superusers))
				{
					// Set administrator if doesn't already have it.  This should never happen except at installation
					if (!$this->is_administrator)
					{
						$this->grant_permission(RPC_User::RPC_AUTHLEVEL_ADMINISTRATOR);
					}
					$this->is_superuser = TRUE;
					$this->raw_perms_int |= self::RPC_AUTHLEVEL_SUPERUSER;
				}
			}
			// No result, so no such user
			else
			{
				$this->error = self::ERR_NO_SUCH_USER;
			}
		}
		// Some database failure and the query didn't finish
		else
		{
			$this->error = self::ERR_DB_ERROR;
		}
		return;
	}
	
	/**
	 * Set the user's name property
	 * Note: This database action does NOT get committed until $this->db->commit() is called!
	 * 
	 * @param string $name 
	 * @access public
	 * @return boolean
	 */
	public function set_name($name)
	{
		// Truncate to 256 chars
		$qryname = $this->db->real_escape_string(substr($name,0,256));
		$qry = sprintf("UPDATE users SET name='%s' WHERE userid=%u;", $qryname, $this->id);
		if ($result = $this->db->query($qry))
		{
			$this->name = $name;
			return TRUE;
		}
		else
		{
			$this->error = self::ERR_DB_ERROR;
			return FALSE;
		}
	}
	/**
	 * Set the user's email address
	 * Note: This database action does NOT get committed until $this->db->commit() is called!
	 * 
	 * @param string $email 
	 * @access public
	 * @return boolean
	 */
	public function set_email($email)
	{
		if (self::validate_email($email))
		{
			$qryemail = $this->db->real_escape_string($email);
			$qry = sprintf("UPDATE users SET email='%s' WHERE userid=%u", $qryemail, $this->id);
			if ($result = $this->db->query($qry))
			{
				$this->email = $email;
				return TRUE;
			}
		}
		else
		{
			$this->error = self::ERR_INVALID_INPUT;
			return FALSE;
		}
	}
	/**
	 * Set user type (STUDENT or TEACHER)
	 * 
	 * @param string $type (STUDENT|TEACHER)
	 * @access public
	 * @return boolean
	 */
	public function set_type($type)
	{
		$type = strtoupper($type);
		if ($type !== 'STUDENT' && $type !== 'TEACHER')
		{
			$this->error = self::ERR_INVALID_TYPE;
			return FALSE;
		}
		else
		{
			$qry = sprintf("UPDATE users SET usertype='%s' WHERE userid=%u;", $type, $this->id);
			if ($result = $this->db->query($qry))
			{
				$this->type = $type;
				return TRUE;
			}
			else
			{
				$this->error = self::ERR_DB_ERROR;
				return FALSE;
			}
		}
	}
	/**
	 * Permanently delete the user's account, and all existing assignments
	 * Superuser accounts CANNOT be deleted
	 * Note: This database action does NOT get committed until $this->db->commit() is called!
	 * 
	 * @access public
	 * @return boolean
	 */
	public function delete_account()
	{
		if ($this->is_superuser)
		{
			$this->error = self::ERR_CANNOT_DELETE_SUPERUSER;
			return FALSE;
		}
		// SQL only explicitly deletes the user id.  All assignments are removed
		// by cascade deletion
		// For administrators and publishers, need to NULL the userid
		// TODO: Find a better way than NULLing the userid

		// The FK will templates these for privileged users.  Unprivileged users
		// need to be manually deleted along with administrator assignments
		if ($this->is_administrator || $this->is_publisher)
		{
			$qry_assign = sprintf("DELETE FROM assignments WHERE userid=%u AND template=0;", $this->id);
		}
		else
		{
			$qry_assign = sprintf("DELETE FROM assignments WHERE userid=%u;", $this->id);
		}
		$result_assign = $this->db->query($qry_assign);


		$qry_user = sprintf("DELETE FROM users WHERE userid=%u;", $this->id);
		$result_user = $this->db->query($qry_user);

		if ($result_user && $result_assign)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Return a multidimensional array of assignments user
	 * Array contains basic brief information about assignments (title,desc,class,start,due)
	 * TODO: Think about whether these should be actual RPC_Assignment objects
	 *       requiring separate queries for each assignment in their constructors
	 * 
	 * @access public
	 * @return array
	 */
	public function get_assignments($status=RPC_User::RPC_ASSIGNMENT_STATUS_ALL)
	{
		$arr_assignments = array();
		switch ($status)
		{
			case RPC_User::RPC_ASSIGNMENT_STATUS_ACTIVE:
				$qry_status = "ACTIVE";
				$qry_order = "ASC";
				break;
			case RPC_User::RPC_ASSIGNMENT_STATUS_INACTIVE:
				$qry_status = "INACTIVE";
				$qry_order = "DESC";
				break;
			case RPC_User::RPC_ASSIGNMENT_STATUS_EXPIRED:
				$qry_status = "EXPIRED";
				$qry_order = "DESC";
				break;
			case RPC_User::RPC_ASSIGNMENT_STATUS_ALL:
			default:
				$qry_status = "ALL";
				$qry_order = "DESC";
				break;
		}
		$qry = sprintf(<<<QRY
			SELECT * FROM
			(
				SELECT
					NULL as linkid,
					id,
					title,
					description,
					class,
					start_date,
					due_date,
					days_left,
					is_shared
				FROM assignments_brief_vw
				WHERE userid=%1\$u AND status='%2\$s'
				UNION
				SELECT
					linkid,
					linked_assignments.assignid as id,
					title,
					description,
					class,
					start_date,
					due_date,
					days_left,
					is_shared
				FROM linked_assignments JOIN assignments_brief_vw ON linked_assignments.assignid = assignments_brief_vw.id
				WHERE linked_assignments.userid=%1\$u AND assignments_brief_vw.status='%2\$s'
			) sub
			ORDER BY due_date %3\$s, title ASC;
QRY
		, $this->id, $qry_status, $qry_order);
		if ($result = $this->db->query($qry))
		{
			while ($row = $result->fetch_assoc())
			{
				// Build URLs
				if ($row['linkid'])
				{
					$row['url'] = RPC_Linked_Assignment::get_url($row['linkid'], "", $this->config);
					$row['url_edit'] = RPC_Linked_Assignment::get_url($row['linkid'], "edit", $this->config);
					$row['url_delete'] = RPC_Linked_Assignment::get_url($row['linkid'], "delete", $this->config);
				}
				else
				{
					$row['url'] = RPC_Assignment::get_url($row['id'], "", $this->config);
					$row['url_edit'] = RPC_Assignment::get_url($row['id'], "edit", $this->config);
					$row['url_delete'] = RPC_Assignment::get_url($row['id'], "delete", $this->config);
				}
				$arr_assignments[] = $row;
			}
			$result->close();
			return $arr_assignments;
		}
		else
		{
			$this->error = self::ERR_DB_ERROR;
			return FALSE;
		}
	}

	/**
	 * Return a multidimensional array of assignment steps due
	 * within $days from the current date.
	 * 
	 * @param integer $days
	 * @access public
	 * @return array
	 */
	public function get_items_due($days)
	{
		$arr_items_due = array();
		if (!ctype_digit(strval($days)))
		{
			$this->error = self::ERR_INVALID_INPUT;
			return FALSE;
		}
		$qry = sprintf(<<<QRY
			SELECT * FROM
			(
				SELECT
					NULL as linkid,
					assignid as id,
					assignment,
					step,
					position,
					due_date,
					days_left
				FROM steps_vw
				WHERE userid=%1\$u AND days_left >= -%2\$u AND days_left <= %2\$u
				UNION
				SELECT
					linked_steps_vw.linkid,
					linked_assignments.assignid as id,
					steps_vw.assignment,
					steps_vw.step,
					steps_vw.position,
					steps_vw.due_date,
					steps_vw.days_left
				FROM linked_steps_vw
					JOIN steps_vw ON linked_steps_vw.id = steps_vw.id
					JOIN linked_assignments ON linked_steps_vw.linkid = linked_assignments.linkid
				WHERE linked_assignments.userid=%1\$u AND days_left >=-%2\$u AND days_left <= %2\$u
			) sub
			ORDER BY days_left ASC, step ASC;
QRY
		, $this->id, $days);
		if ($result = $this->db->query($qry))
		{
			while ($row = $result->fetch_assoc())
			{
				// Build URLs
				if ($row['linkid'])
				{
					$row['url'] = RPC_Linked_Assignment::get_url($row['linkid'], "", $this->config);
					$row['url_edit'] = RPC_Linked_Assignment::get_url($row['linkid'], "edit", $this->config);
					$row['url_delete'] = RPC_Linked_Assignment::get_url($row['linkid'], "delete", $this->config);
				}
				else
				{
					$row['url'] = RPC_Assignment::get_url($row['id'], "", $this->config);
					$row['url_edit'] = RPC_Assignment::get_url($row['id'], "edit", $this->config);
					$row['url_delete'] = RPC_Assignment::get_url($row['id'], "delete", $this->config);
				}
				$arr_items_due[] = $row;
			}
			$result->close();
			return $arr_items_due;
		}
		else
		{
			echo $this->db->error;
			$this->error = self::ERR_DB_ERROR;
			return FALSE;
		}
	}

	/**
	 * Return a multidimensional array of assignment templates available to user 
	 * Regular users only get published templates, and none is editable
	 * Publishers get all published templates, their own unpublished, but only their own are editable
	 * Administrators get all published and unpublished templates
	 * 
	 * @param RPC_User $user User to retrieve templates for
	 * @static
	 * @access public
	 * @return array
	 */
	public static function get_templates($user)
	{
		$qry_sel = <<<QRY
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
				is_published
			FROM templates_vw 
QRY;
		if ($user->is_administrator)
		{
			// No where needed - all templates selected
			$qry_where = "";
		}
		else if ($user->is_publisher)
		{
			$qry_where = sprintf("WHERE userid = %u OR is_published = 1;", $user->id);
		}
		else  // Regular user
		{
			$qry_where = "WHERE is_published = 1;";
		}

		if($result = $user->db->query($qry_sel . $qry_where))
		{
			$arr_templates = array();
			while ($row = $result->fetch_assoc())
			{
				// Add edit permissions to each row
				// 
				if ($user->is_administrator)
				{
					$row['is_editable'] = 1;
				}
				else if ($user->is_publisher)
				{
					if ($row['userid'] == $user->id) $row['is_editable'] = 1;
					else $row['is_editable'] = 0;	
				}
				else
				{
					$row['is_editable'] = FALSE;
				}

				// Direct URLs to this template
				$row['url'] = $user->config->app_use_url_rewrite ? $user->config->app_fixed_web_path . "templates/" . $row['id'] : $user->config->app_fixed_web_path . "/?tmpl=" . $row['id'];
				$row['url_edit'] = $user->config->app_use_url_rewrite ? $row['url'] . "/edit" : $row['url'] . "&action=edit";
				$row['url_delete'] = $user->config->app_use_url_rewrite ? $row['url'] . "/delete" : $row['url'] . "&action=delete";
				$arr_templates[] = $row;
			}
			$result->close();
		}
		else
		{
			$user->error = self::ERR_DB_ERROR;
			return FALSE;
		}
		return $arr_templates;
	}

	/***************** Administrative functions ********************/
	/**
	 * Set the singleton active authority user with permission to modify
	 * this user's permissions
	 *
	 * Returns FALSE if $active_authority_user->id == $this->id or $active_authority_user
	 * currently has any error status
	 * Note: This database action does NOT get committed until $this->db->commit() is called!
	 * 
	 * @param object $active_authority_user RPC_User
	 * @access public
	 * @return boolean
	 */
	public function set_active_authority_user($active_authority_user)
	{
		if ($active_authority_user->id == $this->id || !empty($active_authority_user->error))
		{
			$this->error = self::ERR_CANNOT_SET_AUTHORITY;
			return FALSE;
		}
		else
		{
			$this->active_authority_user = $active_authority_user;
			return TRUE;
		}
	}
	/**
	 * Grant privilege to user
	 * Note: This database action does NOT get committed until $this->db->commit() is called!
	 * 
	 * @param integer $permission Target permission to grant (self::RPC_AUTHLEVEL_USER|self::RPC_AUTHLEVEL_PUBLISHER|self::RPC_AUTHLEVEL_ADMINISTRATOR)
	 * @access public
	 * @return boolean
	 */
	public function grant_permission($permission)
	{
		// Error if authority user isn't set or doesn't have administrator privileges
		// Except if the current user is an auth_superuser defined in config.inc.php
		if ((!isset($this->active_authority_user) || (isset($this->active_authority_user) && !$this->active_authority_user->is_administrator)) && !in_array($this->username, $this->config->auth_superusers))
		{
			$this->error = self::ERR_ACCESS_DENIED;
			return FALSE;
		}
		if (!in_array($permission, array(self::RPC_AUTHLEVEL_USER, self::RPC_AUTHLEVEL_PUBLISHER, self::RPC_AUTHLEVEL_ADMINISTRATOR)))
		{
			$this->error = self::ERR_INVALID_AUTHLEVEL;
			return FALSE;
		}
		// Update database
		$this->raw_perms_int = $permission;
		$qry = sprintf("UPDATE users SET perms = %u WHERE userid = %u;", $this->raw_perms_int, $this->id);
		if ($result = $this->db->query($qry))
		{
			// Set the permission boolean flags
			switch ($permission)
			{
				case self::RPC_AUTHLEVEL_PUBLISHER:
					$this->is_publisher = TRUE;
					$this->is_administrator = FALSE;
					break;
				case self::RPC_AUTHLEVEL_ADMINISTRATOR:
					$this->is_administrator = TRUE;
					$this->is_publisher = FALSE;
					break;
				case self::RPC_AUTHLEVEL_USER:	
					$this->is_user = TRUE;
					$this->is_publisher = FALSE;
					$this->is_administrator = FALSE;
					break;
				default: break;
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
	 * Revoke special privileges from user, restoring RPC_AUTHLEVEL_USER.
	 * Note: This database action does NOT get committed until $this->db->commit() is called!
	 * 
	 * @access public
	 * @return boolean
	 */
	public function revoke_permission()
	{
		// Error if authority user isn't set or doesn't have administrator privileges
		// Except if the current user is an auth_superuser defined in config.inc.php
		if ((!isset($this->active_authority_user) || (isset($this->active_authority_user) && !$this->active_authority_user->is_administrator)) && !$this->username == $this->config->auth_superusers)
		{
			$this->error = self::ERR_ACCESS_DENIED;
			return FALSE;
		}
		// Update database
		$this->raw_perms_int = self::RPC_AUTHLEVEL_USER;
		$qry = sprintf("UPDATE users SET perms = %u WHERE userid = %u;", $this->raw_perms_int, $this->id);
		if ($result = $this->db->query($qry))
		{
			// Set the permission boolean flags
			$this->is_publisher = FALSE;
			$this->is_administrator = FALSE;
			$this->is_user = TRUE;
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
	 * @return string
	 */
	public function get_error()
	{
		if (!empty($this->error))
		{
			switch ($this->error)
			{
				case self::ERR_ACCESS_DENIED: return "You are not allowed to access this resource.";
				case self::ERR_NO_SUCH_USER: return "No such user could be located.";
				case self::ERR_USER_ALREADY_EXISTS: return "The requested user already exists.";
				case self::ERR_CANNOT_SET_AUTHORITY: return "Cannot set authority user.";
				case self::ERR_INVALID_AUTHLEVEL: return "An invalid authlevel was supplied.";
				case self::ERR_DB_ERROR: return "A database error occurred.";
				case self::ERR_INVALID_INPUT: return "Invalid input was supplied.";
				case self::ERR_INVALID_TYPE: return "An invalid user type was supplied. Valid types are STUDENT or TEACHER.";
				case self::ERR_INVALID_QUERY_TYPE: return "Invalid user query.";
				case self::ERR_CANNOT_DELETE_SUPERUSER: return "Superuser accounts cannot be deleted.";
				default: return "";
			}
		}
		else return "";
	}

	// Static functions
	/**
	 * Create a new user in the database by supplying a username
	 * Note: No password will be set for user.  The authentication
	 * API is expected to handle password creation.
	 *
	 * Check RPC_User::username_exists() before calling this to return the proper
	 * error code. You won't get much valuable info back from this function.
	 * 
	 * @param string $username 
	 * @param string $password 
	 * @param string $name 
	 * @param string $email 
	 * @param integer $perms Integer representation of permissions
	 * @param object $db MySQLi database connection
	 * @static
	 * @access public
	 * @return integer Userid of newly created user, or FALSE on failure
	 */
	public static function create_user($username, $name, $email, $type, $perms, $db)
	{
		// Validation
		if (!empty($email))
		{
			if (!self::validate_email($email)) return FALSE;
		}
		if (!ctype_digit(strval($perms))) return FALSE;
		$type = strtoupper($type);
		if ($type !== 'STUDENT' && $type !== 'TEACHER') return FALSE;

		// Can't create a user who already exists (username or email)
		if (!RPC_User::username_exists($username, $db) && !RPC_User::email_exists($email, $db))
		{
			$qry = sprintf("INSERT INTO users (username, name, email, usertype, perms) VALUES ('%s','%s','%s','%s',%u);",
						$db->real_escape_string(substr($username,0,320)),
						$db->real_escape_string(substr($name,0,256)),
						$db->real_escape_string(strtolower($email)),
						$type,
						$perms
			);
			if ($result = $db->query($qry))
			{
				return $db->insert_id;
			}
			else
			{
				return FALSE;
			}
		}
		else return FALSE;
	}
	/**
	 * Test if $username already exists in the database. Return TRUE if
	 * username exists
	 * 
	 * @param string $username 
	 * @param object $db MySQLi database connection
	 * @static
	 * @access public
	 * @return boolean
	 */
	public static function username_exists($username, $db)
	{
		$username = $db->real_escape_string(strtoupper($username));
		$qry = sprintf("SELECT username FROM users WHERE UPPER(username) = '%s';", $username);
		if ($result = $db->query($qry))
		{
			// User exists, return TRUE
			if ($result->num_rows > 0)
			{
				$result->close();
				return self::RPC_EXISTS;
			}
			else
			{
				$result->close();
				return self::RPC_NOT_EXISTS;
			}
		}
		else return self::RPC_EXISTS_ERROR;
	}
	/**
	 * Test if $email address already exists in the system.
	 * Return TRUE if it exists
	 * 
	 * @param string $email
	 * @param object $db MySQLi database connection
	 * @static
	 * @access public
	 * @return boolean
	 */
	public static function email_exists($email, $db)
	{
		$email = $db->real_escape_string(strtoupper($email));
		$qry = sprintf("SELECT email FROM users WHERE UPPER(email) = '%s';", $email);
		if ($result = $db->query($qry))
		{
			// User exists, return TRUE
			if ($result->num_rows > 0)
			{
				$result->close();
				return self::RPC_EXISTS;
			}
			else
			{
				$result->close();
				return self::RPC_NOT_EXISTS;
			}
		}
		else return self::RPC_EXISTS_ERROR;
	}
	/**
	 * Validate an email address
	 * 
	 * @param string $email 
	 * @static
	 * @access public
	 * @return boolean
	 */
	public static function validate_email($email)
	{
		return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
	}

	/**
	 * Return an array of all administrator usernames 
	 * 
	 * @param object $db MySQLi database connection singleton
	 * @static
	 * @access public
	 * @return array
	 */
	public static function get_all_administrators($db)
	{
		$arr_administrators = array();
		$qry = "SELECT username FROM users WHERE perms & " . self::RPC_AUTHLEVEL_ADMINISTRATOR . " <> 0 ORDER BY username ASC;";
		if ($result = $db->query($qry))
		{
			while ($row = $result->fetch_assoc())
			{
				$arr_administrators[] = $row['username'];
			}
			$result->close();
			return $arr_administrators;
		}
		else
		{
			return FALSE;
		}
	}
	/**
	 * Return an array of all publisher usernames 
	 * 
	 * @param object $db MySQLi database connection singleton
	 * @static
	 * @access public
	 * @return array
	 */
	public static function get_all_publishers($db)
	{
		$arr_publishers = array();
		$qry = "SELECT username FROM users WHERE perms & " . self::RPC_AUTHLEVEL_PUBLISHER . " <> 0 ORDER BY username ASC;";
		if ($result = $db->query($qry))
		{
			while ($row = $result->fetch_assoc())
			{
				$arr_publishers[] = $row['username'];
			}
			$result->close();
			return $arr_publishers;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Return an associative array of privileged users including username,
	 * name, publisher, administrator and superuser status
	 * 
	 * @param object $config RPC_Config global configuration singleton
	 * @param object $db MySQLi database connection singleton
	 * @static
	 * @access public
	 * @return array
	 */
	public static function get_all_privileged_users($config, $db)
	{
		$arr_privileged_users = array();
		$authlevel_p = self::RPC_AUTHLEVEL_PUBLISHER;
		$authlevel_a = self::RPC_AUTHLEVEL_ADMINISTRATOR;
		$qry = <<<QRY
		SELECT username, userid, name,
			CASE WHEN perms = $authlevel_p THEN 1 ELSE 0 END AS is_publisher,
			CASE WHEN perms = $authlevel_a THEN 1 ELSE 0 END AS is_administrator,
			0 AS is_superuser
		FROM users
		WHERE perms >= $authlevel_p;
QRY;
		if ($result = $db->query($qry))
		{
			while ($row = $result->fetch_assoc())
			{
				$uname = htmlentities($row['username'], ENT_QUOTES);
				$arr_privileged_users[$uname]['id'] = $row['userid'];
				$arr_privileged_users[$uname]['name'] = htmlentities($row['name']);
				$arr_privileged_users[$uname]['is_publisher'] = $row['is_publisher'] == 1 ? TRUE : FALSE;
				$arr_privileged_users[$uname]['is_administrator'] = $row['is_administrator'] == 1 ? TRUE : FALSE;
				$arr_privileged_users[$uname]['is_superuser'] = in_array($row['username'], $config->auth_superusers) ? TRUE : FALSE;
			}
			$result->close();
			return $arr_privileged_users;
		}
		else
		{
			return FALSE;
		}
	}
}
?>
