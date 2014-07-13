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
	public function testPasswordComplexity()
	{
		$this->assertFalse(Native_User::password_meets_complexity('sh0rt'));
		$this->assertFalse(Native_User::password_meets_complexity('long enough no numbers'));
		// Only numbers
		$this->assertFalse(Native_User::password_meets_complexity('1234567'));
		$this->assertTrue(Native_User::password_meets_complexity('long enough with99 number'));
		$this->assertTrue(Native_User::password_meets_complexity('long enough with number end99'));
		$this->assertTrue(Native_User::password_meets_complexity('99long enough with number start'));
	}
	public function testSetPassword()
	{
		$oldpass = 'abc123';
		$wrongpass = 'wrong';
		$newpass = 'cba321';
		$forcepass = 'zyx987';
		$user4 = new Native_User('user4@example.com', $this->config, $this->db);

		$this->assertTrue($user4->validate_password($oldpass));

		// Wrong old password
		$this->assertFalse($user4->set_password($wrongpass, $newpass));
		$this->assertEquals(Native_User::ERR_INCORRECT_CREDS, $user4->error);
		// Old password still works
		$this->assertTrue($user4->validate_password($oldpass));

		// Complexity
		$this->assertFalse($user4->set_password($oldpass, $wrongpass));
		$this->assertEquals(Native_User::ERR_PASWORD_COMPLEXITY_UNMET, $user4->error);

		// Correctly set
		$this->assertTrue($user4->set_password($oldpass, $newpass));
		// Requery
		$user4 = new Native_User('user4@example.com', $this->config, $this->db);
		$this->assertTrue($user4->validate_password($newpass));

		// Using the $force option to change without oldpass
		$this->assertTrue($user4->set_password(NULL, $forcepass, TRUE));
		// Requery & validate
		$user4 = new Native_User('user4@example.com', $this->config, $this->db);
		$this->assertTrue($user4->validate_password($forcepass));
	}
	public function testSetResetToken()
	{
		$user3 = new Native_User('user3@example.com', $this->config, $this->db);
		$this->assertNull($user3->reset_token);
		$this->assertNull($user3->reset_token_expires);

		// Set the token,  verify it has an expiry
		$user3->set_reset_token();
		$this->assertNotEmpty($user3->reset_token);
		$this->assertGreaterThan(time(), $user3->reset_token_expires);

		// Attempt to query token
		$token = $user3->reset_token;
		$reset_user = Native_User::get_user_by_token($token, $this->config, $this->db);
		$this->assertEquals('user3@example.com', $reset_user->username);
	}
	/**
	 * @depends testSetResetToken
	 */
	public function testExpiredResetToken()
	{
		// Set a past expiry
		$this->db->query("UPDATE users SET reset_token = 'token', reset_token_expires = NOW() - INTERVAL 10 SECOND WHERE userid = 2");
		$this->assertFalse(Native_User::get_user_by_token('token', $this->config, $this->db));
	}
}
?>
