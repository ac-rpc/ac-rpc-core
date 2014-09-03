<?php
class RPC_UserTemplatesTest extends RPC_PHPUnit_Extensions_Databaase_TestCase
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
	public function testEditOwnTemplate()
	{
		$user5_owner = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 5, $this->config, $this->db);
		
		$template_owner = new RPC_Template(8, $user5_owner, $this->config, $this->db);
		$template_other = new RPC_Template(9, $user5_owner, $this->config, $this->db);
		
		// User 5 can write to template 8, but not 9
		$this->assertTrue($template_owner->is_editable);
		$this->assertFalse($template_other->is_editable);
	}
}
?>
