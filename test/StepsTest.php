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
	public function testUpdateStep()
	{
		
	}
}
?>
