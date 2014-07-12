<?php
class RPC_TemplatesTest extends RPC_PHPUnit_Extensions_Databaase_TestCase
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
		$this->config->app_use_url_rewrite = TRUE;
		$user1 = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 1, $this->config, $this->db);
		$assign = new RPC_Template(5, $user1, $this->config, $this->db);

		$this->assertEquals('http://www.example.com/path/to/application/templates/5', RPC_Template::get_url(5, "", $this->config));
		$this->assertEquals('http://www.example.com/path/to/application/templates/5/delete', RPC_Template::get_url(5, "delete", $this->config));
		// Bad verb, defaults to view URL
		$this->assertEquals('http://www.example.com/path/to/application/templates/5', RPC_Template::get_url(5, "unknown", $this->config));

		$this->config->app_use_url_rewrite = FALSE;
		$this->assertEquals('http://www.example.com/path/to/application/?tmpl=5&action=delete', RPC_Template::get_url(5, "delete", $this->config));
	}
}
?>
