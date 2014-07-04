<?php
abstract class RPC_PHPUnit_Extensions_Databaase_TestCase extends PHPUnit_Extensions_Database_TestCase
{
  public $fixture_path;
	static private $pdo = null;
	protected $conn = null;

	public function getConnection()
	{
		if ($this->conn === null) {
			if (self::$pdo == null) {
				self::$pdo = new PDO($GLOBALS['PHPUNIT_DB_DSN'], $GLOBALS['PHPUNIT_DB_USER'], $GLOBALS['PHPUNIT_DB_PASS']);
			}
			$this->conn = $this->createDefaultDBConnection(self::$pdo, $GLOBALS['PHPUNIT_DB_NAME']);
		}
		return $this->conn;
	}
	public function getSetUpOperation()
	{
		$this->fixture_path = __DIR__ . '/../fixtures/';

		$cascadeTruncates = TRUE; //if you want cascading truncates, false otherwise
		//if unsure choose false

		return new PHPUnit_Extensions_Database_Operation_Composite(array(
			new PHPUnit_Extensions_Database_Operation_MySQL55Truncate($cascadeTruncates),
			PHPUnit_Extensions_Database_Operation_Factory::INSERT()
		));
	}
}
?>
