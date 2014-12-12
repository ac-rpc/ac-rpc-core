<?php
class RPC_StepsTest extends RPC_PHPUnit_Extensions_Databaase_TestCase
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
	public function testUpdateStepValidUser()
	{
		$user1 = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 1, $this->config, $this->db);
		$step1 = new RPC_Step(1, $user1, $this->config, $this->db);

		// Step was created
		$this->assertEquals('User 1 Assignment 1 Step 1', $step1->title);

		// Change a property and save
		$new_title = 'Updated Title';
		$step1->title = $new_title;
		$step1->update();
		$this->assertEquals($new_title, $step1->title);

	}
	public function testUpdateStepInvalidUser()
	{
		$user2 = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 2, $this->config, $this->db);
		$step1 = new RPC_Step(1, $user2, $this->config, $this->db);

		$new_title = 'Updated Title';
		$step1->title = $new_title;
		$this->assertFalse($step1->update());
		$this->assertEquals(RPC_Step::ERR_ACCESS_DENIED, $step1->error);
	}
}
?>
