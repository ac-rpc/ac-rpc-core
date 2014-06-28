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
 * Class RPC_DB 
 * 
 * Database connection object singleton
 *
 * @package RPC
 */
class RPC_DB
{
	/**
	 * Connection singleton member
	 * 
	 * @param object $config RPC_Config configuration singleton
	 * @var object PDO database connection
	 * @static
	 * @access private
	 */
	private static $_connection = NULL;	

	// Inaccessible singleton constructor/clone methods
	private function __construct() {}
	private function __clone() {}

	/**
	 * Retrieve the connection singleton
	 * 
	 * @param object $config RPC_Config configuration singleton
	 * @static
	 * @access public
	 * @return object PDO database connection
	 */
	public static function get_connection($config)
	{
		// Invalid or undefined configuration object is fatal
		if (!is_object($config) || !get_class($config) == 'RPC_Config')
		{
			$err = 'Fatal error: Object $config is not initialized or not of type RPC_Config.';
			echo $err;
			error_log($err);
			exit();
		}
		if (self::$_connection == NULL)
		{
			try
			{
				self::$_connection = new \PDO("mysql:host={$config->db_host};dbname={$config->db_name};port={$config->db_port}", $config->db_user, $config->db_pass);
				self::$_connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
				self::$_connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC)
			}
			catch (\PDOException $e)
			{
				$err = "Fatal error connecting to database. Please see your server's error log for details.";
				echo $err;
				error_log($err . ": (" . $e->getCode() . ") " . $e->getMessage());
				exit();
			}
		}
		return self::$_connection;
	}
}
?>
