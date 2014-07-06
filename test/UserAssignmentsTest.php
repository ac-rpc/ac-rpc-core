<?php
class RPC_UserAssignmentsTest extends RPC_PHPUnit_Extensions_Databaase_TestCase
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
	public function testLoadUserTemplates()
	{
		$user1_admin = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 1, $this->config, $this->db);
		// Should have 2 templates
		$this->assertEquals(2, count(RPC_User::get_templates($user1_admin)));

		// Other user can't see unpublished template
		$user2 = new RPC_User(RPC_User::RPC_QUERY_USER_BY_USERNAME, 'testuser2@example.com', $this->config, $this->db);
		$this->assertEquals(1, count(RPC_User::get_templates($user2)));

		// Grant publisher to user2, can then see 2 templates
		$user2->set_active_authority_user($user1_admin);
		$user2->grant_permission(RPC_User::RPC_AUTHLEVEL_PUBLISHER);
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
	public function testSharedAssignmentPermissions()
	{
		$user1_owner = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 1, $this->config, $this->db);
		$user2_viewer = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 2, $this->config, $this->db);

		// User2 can see user1's shared assignment
		$assign_shared = new RPC_Assignment(4, $user2_viewer, $this->config, $this->db);
		$this->assertNull($assign_shared->error);
		// And properties are visible
		$this->assertEquals('User 1 Assignment 3 Active Shared', $assign_shared->title);
		
		// User2 cannot see the unshared assignment
		$assign_unshared = new RPC_Assignment(1, $user2_viewer, $this->config, $this->db);
		$this->assertEquals(RPC_Assignment::ERR_ACCESS_DENIED, $assign_unshared->error);
		// Properties are not readable
		$this->assertNull($assign_unshared->title);

		// Load from the owned user, grant shared and check again
		$assign_unshared = new RPC_Assignment(1, $user1_owner, $this->config, $this->db);
		$assign_unshared->is_shared = TRUE;
		$assign_unshared->update();

		$assign_unsahred = NULL;
		$assign_nowshared = new RPC_Assignment(1, $user2_viewer, $this->config, $this->db);
		$this->assertNull($assign_nowshared->error);
		$this->assertEquals('User 1 Assignment 1', $assign_nowshared->title);

		// But it still isn't editable
		$this->assertFalse($assign_nowshared->is_editable);
		$assign_nowshared->title = "Modified by other user";
		$assign_nowshared->update();
		$this->assertEquals(RPC_Assignment::ERR_READONLY, $assign_nowshared->error);
		// Reload and the title is unchanged
		$assign_nowshared = new RPC_Assignment(1, $user1_owner, $this->config, $this->db);
		$this->assertEquals('User 1 Assignment 1', $assign_nowshared->title);
	}
}
?>
