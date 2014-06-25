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
 * Class Native_User
 * RPC users utilizing the 'native' authentication plugin
 *
 * @package RPC
 */
class Native_User extends RPC_User
{
	const ERR_CANNOT_RESET_PASSWORD = 101;
	const ERR_CANNOT_SEND_PASSWORD = 102;
	const ERR_INCORRECT_CREDS = 103;
	const ERR_CANNOT_SET_COOKIE = 104;
	const ERR_PASWORD_COMPLEXITY_UNMET = 105;

	/**
	 * SHA1 sum of password stored
	 *
	 * @var string
	 * @access public
	 */
	public $password_hash;
	/**
	 * Password salt
	 * Obsolete for passwords hashed by password_hash()
	 *
	 * @var string
	 * @access private
	 */
	private $salt;
	/**
	 * Hashing algorithm (legacy sha1 or bcrypt)
	 * @var string
	 * @access private
	 */
	private $hashtype;
	/**
	 * Authentication session ID used to identify browser session and paired with token
	 * A single user may have several valid sessions in different browser cookies.
	 *
	 * @var mixed
	 * @access public
	 */
	public $session;
	/**
	 * Authentication token, used to validate cookie and changed on every page access
	 * to reduce the effectiveness of cookie theft
	 *
	 * @var string
	 * @access public
	 */
	public $token;
	/**
	 * @var string
	 * @access private
	 */
	private $reset_token;
	/**
	 * @var int
	 * @access private
	 */
	private $reset_token_expires;
	/**
	 * User has successfully supplied credentials
	 *
	 * @var boolean
	 * @access public
	 */
	public $is_authenticated = FALSE;

	/**
	 * Constructor implements parent constructor, adding password and
	 * authentication information
	 *
	 * @param string $username User to retrieve
	 * @param object $config RPC_Config configuration singleton
	 * @param object $db RPC_DB MySQLi database connection singleton
	 * @access public
	 * @return RPC_User
	 */
	public function __construct($username, $config, $db)
	{
		$this->config = $config;
		$this->db = $db;
		$dbusername = $this->db->real_escape_string(strtoupper($username));
		$qry = sprintf("SELECT userid, username, password, passwordsalt, hashtype, email, name, usertype, perms, token FROM users WHERE UPPER(username) = '%s'", $dbusername);
		if ($result = $this->db->query($qry))
		{
			// Got results, load object
			if ($row = $result->fetch_assoc())
			{
				$this->id = intval($row['userid']);
				$this->username = htmlentities($row['username'], ENT_QUOTES);
				// If password hasn't been set yet, it will be NULL but $this->set_password expects an empty string,
				// and the empty string must be hashed.
				$this->password_hash = $row['password'] !== NULL ? $row['password'] : sha1("");
				$this->salt = $row['passwordsalt'];
				$this->hashtype = $row['hashtype'];
				$this->token = $row['token'];
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
	 * Native users have email and usernames the same.
	 * This overrides RPC_User::set_email() to also update
	 * the username
	 *
	 * @param string $email
	 * @access public
	 * @return boolean
	 */
	public function set_email($email)
	{
		if (self::validate_email($email))
		{
			$qryemail = $this->db->real_escape_string(strtolower($email));
			$qry = sprintf("UPDATE users SET username='%s', email='%s' WHERE userid=%u", $qryemail, $qryemail, $this->id);
			if ($result = $this->db->query($qry))
			{
				$this->username = $email;
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
	 * Authenticate user with a password
	 *
	 * @param string $password
	 * @param boolean $stay_logged_in On successful login, should the login be persistent beyond this session?
	 * @access public
	 * @return boolean
	 */
	public function validate_password($password, $stay_logged_in=FALSE)
	{
		// Validate legacy sha1 passwords, and rehash them if successful
		if ($this->hashtype == 'sha1')
		{
			$qry = sprintf("SELECT 1 FROM users WHERE username = '%s' AND password = '%s';",
				$this->db->real_escape_string($this->username),
				$this->db->real_escape_string(sha1($password . $this->salt))
		  );
			if ($result = $this->db->query($qry))
			{
				if ($result->num_rows === 1)
				{
					// Legacy users need to be rehashed into bcrypt
					if ($this->_set_password($password)) $this->db->commit();

					$this->set_authenticated();
				}
				// Bad password
				else
				{
					$this->error = self::ERR_INCORRECT_CREDS;
					return FALSE;
				}
			}
		}
		else if ($this->hashtype == 'bcrypt')
		{
			$qry = sprintf("SELECT password FROM users WHERE username = '%s';", $this->db->real_escape_string($this->username));
			if ($result = $this->db->query($qry))
			{
				// Successful authentication
				if ($result->num_rows === 1)
				{
					$row = $result->fetch_assoc();
					if (password_verify($password, $row['password']))
					{
						$this->set_authenticated();
					}
					// Bad password
					else
					{
						$this->error = self::ERR_INCORRECT_CREDS;
						return FALSE;
					}
				}
				else
				{
					$this->error = self::ERR_INCORRECT_CREDS;
					return FALSE;
				}
			}
		}
		// Invalid hash type - unhandled
		else
		{
			return FALSE;
		}
		if ($this->is_authenticated)
		{
			// Set a permanent cookie if requested
			if ($stay_logged_in)
			{
				$this->start_session();
			}
			return TRUE;
		}
		return FALSE;
	}
	/**
	 * Set the user's status to authenticated. Also sets username in $_SESSION
	 *
	 * @access public
	 * @return void
	 */
	public function set_authenticated()
	{
		$this->is_authenticated = TRUE;
		$_SESSION['username'] = $this->username;

		// Invalidate reset tokens
		if (!empty($this->reset_token)) {
			$this->clear_reset_token();
		}
		$this->_update_last_login();
	}
	/**
	 * Create a new session id in native_sessions
	 *
	 * @access public
	 * @return void
	 */
	public function start_session()
	{
		// Just remove the old one.
		$this->destroy_session();
		$session = $this->db->real_escape_string(md5(time() . rand()));
		$token = $this->db->real_escape_string(md5(time() . rand()));
		$qry = sprintf("INSERT INTO native_sessions (userid, session, token) VALUES (%u, '%s', '%s');", $this->id, $session, $token);
		if ($result = $this->db->query($qry))
		{
			$this->db->commit();
			$this->session = $session;
			$this->token = $token;
			$this->set_cookie();
		}
	}
	/**
	 * Delete native_sessions record for this cookie login session
	 *
	 * @access public
	 * @return void
	 */
	public function destroy_session()
	{
		$qry = sprintf("DELETE FROM native_sessions WHERE userid = %u AND session = '%s';", $this->id, $this->db->real_escape_string($this->session));
		if ($result = $this->db->query($qry))
		{
			$this->db->commit();
			$this->session = "";
			$this->token = "";
		}
		return;
	}
	/**
	 * Generate a new authentication token for user
	 * Called by self::validate_cookie() so a new token is generated
	 * on each page access.
	 *
	 * @access public
	 * @return void
	 */
	public function set_token()
	{
		$token = md5(time() . rand());
		$qry = sprintf("UPDATE native_sessions SET token = '%s' WHERE userid = %u AND session = '%s';", $this->db->real_escape_string($token), $this->id, $this->db->real_escape_string($this->session));
		if ($result = $this->db->query($qry))
		{
			$this->db->commit();
			$this->token = $token;
		}
		return;
	}
	/**
	 * Set an authentication cookie for this user
	 *
	 * @access public
	 * @return boolean
	 */
	public function set_cookie()
	{
		// User must already be signed in.
		if (!$this->is_authenticated)
		{
			$this->error = self::ERR_CANNOT_SET_COOKIE;
			return FALSE;
		}
		// Cookie value is base64 encoded user|token
		$value = base64_encode($this->username . "|" . $this->session . "|" . $this->token);
		// Expire in ten years
		$expire = time() + (24*3600*356*10);
		$cookie_path = $this->config->app_relative_web_path == "" ? "/" : $this->config->app_relative_web_path;
		$cookie = setcookie('RPCAUTH', $value, $expire, $cookie_path, $_SERVER['SERVER_NAME']);
		return $cookie;
	}
	/**
	 * Remove the authentication cookie
	 *
	 * @access public
	 * @return boolean
	 */
	public function unset_cookie()
	{
		$cookie_path = $this->config->app_relative_web_path == "" ? "/" : $this->config->app_relative_web_path;
		return setcookie('RPCAUTH', '', time() - 86400, $cookie_path, $_SERVER['SERVER_NAME']);
	}
	/**
	 * Validate the RPCAUTH native authentication cookie
	 *
	 * @param object $db MySQLi database connection singleton
	 * @static
	 * @access public
	 * @return string Username of validated user
	 */
	public static function validate_cookie($db)
	{
		if ($cookie = self::parse_cookie())
		{
			$qry = sprintf(<<<QRY
				SELECT 1
				FROM native_sessions JOIN users ON native_sessions.userid = users.userid
				WHERE users.username = '%s' AND native_sessions.session = '%s' AND native_sessions.token = '%s';
QRY
				, $db->real_escape_string($cookie['username']),
				  $db->real_escape_string($cookie['session']),
				  $db->real_escape_string($cookie['token'])
			);
			if ($result = $db->query($qry))
			{
				// Successful cookie validation, return username.
				if ($result->num_rows == 1)
				{
					$result->close();
					return $cookie['username'];
				}
				else
				{
					$result->close();
					return FALSE;
				}
			}
		}
		// Cookie wasn't set or was invalid
		else return FALSE;
	}
	/**
	 * Parse the RPCAUTH native authentication cookie and return an associative array
	 * of the cookie's components
	 *
	 * @static
	 * @access public
	 * @return array
	 */
	public static function parse_cookie()
	{
		if (isset($_COOKIE['RPCAUTH']))
		{
			$decoded = base64_decode($_COOKIE['RPCAUTH']);
			$parts = preg_split("/\|/", $decoded);
			if (count($parts) == 3)
			{
				// Return an associative array of cookie components
				return array('username'=>$parts[0], 'session'=>$parts[1], 'token'=>$parts[2]);
			}
			else return FALSE;
		}
		else return FALSE;
	}
	/**
	 * Set user's password to a SHA1sum of $newpassword
	 * User's old password must also be supplied for verification
	 * Password complexity requirement is minimum 6 characters, at least one digit
	 * This function wraps Native_User->_set_password(), which actually does the database
	 * transaction, without requiring $oldpassword.
	 * Note: This database action does NOT get committed until $this->db->commit() is called!
	 *
	 * @param string $oldpassword
	 * @param string $newpassword
	 * @param boolean $force Force the password to change even if the correct old password wasn't supplied
	 * @access public
	 * @return boolean
	 */
	public function set_password($oldpassword, $newpassword, $force = FALSE)
	{
		if ($force == FALSE && !password_verify($oldpassword, $this->password_hash))
		{
			$this->error = self::ERR_INCORRECT_CREDS;
			return FALSE;
		}
		if (!self::password_meets_complexity($newpassword))
		{
			$this->error = self::ERR_PASWORD_COMPLEXITY_UNMET;
			return FALSE;
		}
		if ($this->_set_password($newpassword))
		{
			return TRUE;
		}
		else return FALSE;
	}
	/**
	 * Set the user's password to a SHA1 hash of $newpassword and create a new salt
	 *
	 * @param string $newpassword
	 * @access private
	 * @return boolean
	 */
	private function _set_password($newpassword)
	{
		$newpassword_hash = $this->db->real_escape_string(password_hash($newpassword, PASSWORD_DEFAULT, array('cost' => 12)));

		// Updated hashes as bcrypt stores the salt with the hash, so the salt column is legacy
		$hashinfo = password_get_info($newpassword_hash);
		$qry = sprintf("UPDATE users SET password = '%s', passwordsalt = 'BCRYPT-UNUSED', hashtype = '%s'  WHERE userid = %u;", $newpassword_hash, $hashinfo['algoName'], $this->id);
		if ($result = $this->db->query($qry))
		{
			$this->db->commit();
			$this->password_hash = $newpassword_hash;
			$this->salt = NULL;
			$this->hashtype = 'bcrypt';
			return TRUE;
		}
		else
		{
			$this->error = self::ERR_DB_ERROR;
			return FALSE;
		}
	}
	/**
	 * Update the last_login timestamp
	 * 
	 * @access private
	 * @return bool
	 */
	private function _update_last_login()
	{
		// Update the login timestamp
		if ($this->db->query(sprintf("UPDATE users SET last_login = NOW() WHERE username = '%s';", $this->db->real_escape_string($this->username))))
		{
			$this->db->commit();
			return TRUE;
		}
		return FALSE;
	}
	/**
	 * Set a new random password for the user and send
	 * it via a password reminder email
	 * Note: This database action does NOT get committed until $this->db->commit() is called!
	 *
	 * @access public
	 * @return boolean
	 */
	public function recover_password()
	{
		$token = $this->_set_reset_token();
		$smarty = new RPC_Smarty($this->config);
		$smarty->assign('token', $token);

		// Set headers
		$version = phpversion();
		$headers = <<<HEADERS
From: {$this->config->app_rfc_email_address}
X-Mailer: PHP/$version
HEADERS;

		// Build mail body from template
		$mail_body = $smarty->global_fetch('notifications/native_pw_recovery.tpl');
		// Send the email notification
		if (mail($this->email, $this->config->app_long_name . " password recovery", $mail_body, $headers, "-f{$this->config->app_email_from_address}"))
		{
			return TRUE;
		}
		else
		{
			$this->error = self::ERR_CANNOT_SEND_PASSWORD;
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
				case self::ERR_CANNOT_RESET_PASSWORD: return "Could not reset password.";
				case self::ERR_CANNOT_SEND_PASSWORD: return "Could not send password recovery message.";
				case self::ERR_PASWORD_COMPLEXITY_UNMET: return "New password does not meet minimum complexity requirements.";
				case self::ERR_INCORRECT_CREDS: return "Password was incorrect.";
				case self::ERR_CANNOT_SET_COOKIE: return "Could not set login cookie.";
				default: return parent::get_error();
			}
		}
		else return "";
	}

	/**
	 * Return TRUE if $password meets complexity requirements
	 * Minimum six characters, at least one digit
	 *
	 * @param string $password
	 * @static
	 * @access public
	 * @return boolean
	 */
	public static function password_meets_complexity($password)
	{
		return preg_match('/^.*(?=.{6,})(?=.*\d).*$/', $password);
	}
	/**
	 * Create a new random salt string, used with recovery tokens
	 * In legacy systems this was used to set a new password for recovery
	 *
	 * @access private
	 * @return string
	 */
	private function _set_reset_token()
	{
		$newpass = '';
		$arr_pass = array();
		// Choose some random upper/lower letters
		for ($i = 0; $i < 4; $i++)
		{
			// Case is a 0 or 1 multiplier to push chr() into the lower case ASCII range
			$case = rand(0,1);
			$arr_pass[] = chr(rand(65, 90) + ($case * 32));
		}
		// And some random digits
		for ($i = 0; $i < 6; $i++)
		{
			$arr_pass[] = strval(rand(0, 9));
		}
		// Mix it all up
		shuffle($arr_pass);
		// Stick it together as a string.
		$newpass = implode('', $arr_pass);
		// Hash the username with the random string
		$this->reset_token = sha1($this->username . $newpass);
		$this->reset_token_expires = time() + 86400;

		// Store the new token
    $qry = sprintf("UPDATE users SET reset_token = '%s', reset_token_expires = NOW() + INTERVAL 1 DAY WHERE userid = %u", $this->reset_token, $this->id);
    if ($this->db->query($qry))
    {
			$this->db->commit();
			return $this->reset_token;
    }
    return FALSE;
	}
  /**
   * Clear the reset token
   *
   * @access private
   * @return bool
   */
  public function clear_reset_token()
  {
    if ($this->db->query(sprintf("UPDATE users SET reset_token = NULL, reset_token_expires = NULL WHERE userid = %u", $this->id)))
    {
		$this->db->commit();
		return TRUE;
    }
    return FALSE;
  }
  /**
   * Retrieve a Native_User object by reset token
   *
   * @param string $token
   * @param RPC_Config $config
   * @param MySQLi $db
   * @static
   * @access public
   * @return Native_User
   */
  public static function get_user_by_token($token, $config, $db)
  {
    $qry = sprintf("SELECT username FROM users WHERE reset_token = '%s' AND reset_token_expires >= NOW()", $db->real_escape_string($token));
    if ($result = $db->query($qry))
    {
		if ($result->num_rows === 1)
		{
			$row = $result->fetch_assoc();
			$user = new self($row['username'], $config, $db);

			return $user;
		}
    }
    return FALSE;
  }
}
?>
