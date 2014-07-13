<?php
class RPC_UserTest extends RPC_PHPUnit_Extensions_Databaase_TestCase
{
	protected $dataset;
	protected $config;
	protected $db;

	protected function getDataSet()
	{
		parent::getDataSet();
		$data = new PHPUnit_Extensions_Database_DataSet_YamlDataSet("{$this->fixture_path}/users.yml");
		$data->addYamlFile("{$this->fixture_path}/assignments.yml");
		$data->addYamlFile("{$this->fixture_path}/steps.yml");
		return $data;
	}
	public function setUp()
	{
		parent::setUp();
		$this->dataset = $this->getConnection()->createDataSet();
		$this->config = RPC_Config::get_instance(__DIR__ . '/fixtures/config.inc.php');
		$this->db = RPC_DB::get_connection($this->config);
	}
	public function tearDown()
	{
		parent::tearDown();
		// Clear any authority users
		$user = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 1, $this->config, $this->db);
		$user->set_active_authority_user(NULL);
		$this->config = NULL;
		$this->db = NULL;
	}
	public function testLoadUser()
	{
		// Load user 1 by id
		$user = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 1, $this->config, $this->db);
		$this->assertEquals('Test User 1', $user->name);

		// Load user 2 by username
		$user2 = new RPC_User(RPC_User::RPC_QUERY_USER_BY_USERNAME, 'testuser2@example.com', $this->config, $this->db);
		$this->assertEquals(2, $user2->id);
	}
	public function testModifyUserProperties()
	{
		$user = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 1, $this->config, $this->db);
		$user->set_email('user1newemail@EXAMPLE.com');
		$user->set_name('New Name');
		$user->set_type('STUDENT');

		$user = null;
		$user = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 1, $this->config, $this->db);
		$this->assertEquals('user1newemail@example.com', $user->email);
		$this->assertEquals('New Name', $user->name);
		$this->assertEquals('STUDENT', $user->type);
	}
	public function testSuperUserFromConfig()
	{
		$user1 = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 1, $this->config, $this->db);
		$this->assertTrue($user1->is_superuser);

		$user2 = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 2, $this->config, $this->db);
		$this->assertFalse($user2->is_superuser);
	}
	public function testGrantUserPermissions()
	{
		// User3 fixture has only user perms
		$user3 = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 3, $this->config, $this->db);
		$this->assertFalse($user3->is_publisher);
		$user3->grant_permission(RPC_User::RPC_AUTHLEVEL_PUBLISHER);
		// No active authority user, access denied
		$this->assertEquals(RPC_User::ERR_ACCESS_DENIED, $user3->error);
		$this->assertFalse($user3->is_publisher);

		// Grant with correct authority user
		$user3 = null;
		$user3 = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 3, $this->config, $this->db);
		$user1_admin = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 1, $this->config, $this->db);
		$user3->set_active_authority_user($user1_admin);
		$user3->grant_permission(RPC_User::RPC_AUTHLEVEL_PUBLISHER);
		$this->assertTrue($user3->is_publisher);

		// Verify it was saved
		$user3 = null;
		$user3 = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 3, $this->config, $this->db);
		$this->assertTrue($user3->is_publisher);

		// Grant with bad authority user
		$user2_admin = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 2, $this->config, $this->db);
		$user3 = null;
		$user3 = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 3, $this->config, $this->db);
		$user3->set_active_authority_user($user2_admin);
		$user3->grant_permission(RPC_User::RPC_AUTHLEVEL_ADMINISTRATOR);
		$this->assertEquals(RPC_User::ERR_ACCESS_DENIED, $user3->error);
		$this->assertFalse($user3->is_administrator);

		// Cannot grant superuser
		$user3->set_active_authority_user($user1_admin);
		$user3->grant_permission(RPC_User::RPC_AUTHLEVEL_SUPERUSER);
		$this->assertEquals(RPC_User::ERR_INVALID_AUTHLEVEL, $user3->error);
		$this->assertFalse($user3->is_superuser);

	}
	public function testRevokeUserPermissions()
	{
		$user1_admin = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 1, $this->config, $this->db);
		$user4 = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 4, $this->config, $this->db);
		$user4->set_active_authority_user($user1_admin);
		$user4->revoke_permission(RPC_User::RPC_AUTHLEVEL_ADMINISTRATOR);

		$user4 = null;
		$user4 = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 4, $this->config, $this->db);
		$this->assertFalse($user4->is_administrator);
	}
	public function testDeleteAccount()
	{
		$user2 = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 2, $this->config, $this->db);
		$this->assertEquals(1, $this->getConnection()->getRowCount('assignments', 'userid = 2'));
		$this->assertEquals(1, $this->getConnection()->getRowCount('steps', 'assignid = 6'));
		$user2->delete_account();

		// Should not be able to load the user anymore
		$user2 = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 2, $this->config, $this->db);
		$this->assertEquals(RPC_User::ERR_NO_SUCH_USER, $user2->error);

		// Assignment and steps should be deleted
		$this->assertEquals(0, $this->getConnection()->getRowCount('assignments', 'userid = 2'));
		$this->assertEquals(0, $this->getConnection()->getRowCount('steps', 'assignid = 6'));
	}
	public function testAnonymousUser()
	{
		$anon = new RPC_User(RPC_User::RPC_DO_NOT_QUERY, NULL, $this->config, $this->db);
		$this->assertNull($anon->error);
	}
}
