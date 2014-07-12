<?php
class RPC_Native_User_Test extends RPC_PHPUnit_Extensions_Databaase_TestCase
{
	protected $dataset;
	protected $config;
	protected $db;

	protected function getDataSet()
	{
		parent::getDataSet();
		$data = new PHPUnit_Extensions_Database_DataSet_YamlDataSet("{$this->fixture_path}/users.yml");
		return $data;
	}
	public function setUp()
	{
		parent::setUp();
		$this->dataset = $this->getConnection()->createDataSet();
		$this->config = RPC_Config::get_instance(__DIR__ . '/fixtures/config.inc.php');
		$this->db = RPC_DB::get_connection($this->config);
	}
	public function testLoadUser()
	{
		// Wrong username
		$user1 = new Native_User('user1@example.com', $this->config, $this->db);
		$this->assertEquals(Native_User::ERR_NO_SUCH_USER, $user1->error);
	
		$user1 = new Native_User('testuser1@localhost.localdomain', $this->config, $this->db);
		$this->assertEquals('testuser1@localhost.localdomain', $user1->username);
	}
	public function testBadLegacyPassword()
	{
		$user1 = new Native_User('testuser1@localhost.localdomain', $this->config, $this->db);
		$this->assertFalse($user1->validate_password('wrong'));
		// The hash has not been upgraded
		$this->assertEquals(sha1('abc123' . $user1->get_salt()), $user1->password_hash);
		// And is still not loggedin
		$this->assertFalse($user1->is_authenticated);

	}
	public function testGoodLegacyPasswordUpgrade()
	{
		$user1 = new Native_User('testuser1@localhost.localdomain', $this->config, $this->db);
		// Validate the right password, which will upgrade the hash
		$this->assertTrue($user1->validate_password('abc123'));
		
		$this->assertEquals('bcrypt', $user1->get_hashtype());
		// And is now loggedin
		$this->assertTrue($user1->is_authenticated);
	}
	public function testBadBcryptPassword()
	{
		$user3 = new Native_User('user3@example.com', $this->config, $this->db);
		$this->assertFalse($user3->validate_password('wrong'));
		$this->assertEquals(Native_User::ERR_INCORRECT_CREDS, $user3->error);
		// And is still not loggedin
		$this->assertFalse($user3->is_authenticated);
	}
	public function testGoodBcryptPassword()
	{
		$user3 = new Native_User('user3@example.com', $this->config, $this->db);
		$this->assertTrue($user3->validate_password('abc123'));
		// And is now loggedin
		$this->assertTrue($user3->is_authenticated);
	}
}
?>
