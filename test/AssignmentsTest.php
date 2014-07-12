<?php
class RPC_AssignmentsTest extends RPC_PHPUnit_Extensions_Databaase_TestCase
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
	public function testGetURL()
	{
		$user1 = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 1, $this->config, $this->db);
		$assign = new RPC_Assignment(1, $user1, $this->config, $this->db);

		// Empty and known verb
		$this->assertEquals('http://www.example.com/path/to/application/assignments/1', RPC_Assignment::get_url(1, "", $this->config));
		$this->assertEquals('http://www.example.com/path/to/application/assignments/1/delete', RPC_Assignment::get_url(1, "delete", $this->config));

		// Bad verb, defaults to view URL
		$this->assertEquals('http://www.example.com/path/to/application/assignments/1', RPC_Assignment::get_url(1, "unknown", $this->config));

		$this->config->app_use_url_rewrite = FALSE;
		$this->assertEquals('http://www.example.com/path/to/application/?assign=1&action=delete', RPC_Assignment::get_url(1, "delete", $this->config));
		$this->config->app_use_url_rewrite = TRUE;
	}
}
?>
