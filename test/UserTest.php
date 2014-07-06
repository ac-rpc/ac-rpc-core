<?php
class RpcTest extends RPC_PHPUnit_Extensions_Databaase_TestCase
{
	protected $dataset;
	protected $config;
	protected $db;

	protected function getDataSet()
	{
		parent::getDataSet();
		$data = new PHPUnit_Extensions_Database_DataSet_YamlDataSet("{$this->fixture_path}/users.yml");
		$data->addYamlFile("{$this->fixture_path}/assignments.yml");
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

		$user = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 1, $this->config, $this->db);
		$this->assertEquals('user1newemail@example.com', $user->email);
	}
	public function testModifyUserPermissions()
	{

	}
	public function testLoadUserTemplates()
	{
		$user1 = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 1, $this->config, $this->db);
		// Should have 2 templates
		$this->assertEquals(2, count(RPC_User::get_templates($user1)));

		// Other user can't see unpublished template
		$user2 = new RPC_User(RPC_User::RPC_QUERY_USER_BY_USERNAME, 'testuser2@example.com', $this->config, $this->db);
		$this->assertEquals(1, count(RPC_User::get_templates($user2)));

	}
	public function testLoadUserAssignments()
	{
		$user1 = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 1, $this->config, $this->db);

		// Total 4
		// Inactive 1
		// Active 2
		// Expired 1
		$this->assertEquals(4, count($user1->get_assignments()));
		$this->assertEquals(2, count($user1->get_assignments(RPC_User::RPC_ASSIGNMENT_STATUS_ACTIVE)));
		$this->assertEquals(1, count($user1->get_assignments(RPC_User::RPC_ASSIGNMENT_STATUS_INACTIVE)));
		$this->assertEquals(1, count($user1->get_assignments(RPC_User::RPC_ASSIGNMENT_STATUS_EXPIRED)));

	}
}
