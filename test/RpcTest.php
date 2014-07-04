<?php
class RpcTest extends RPC_PHPUnit_Extensions_Databaase_TestCase
{
	protected function getDataSet()
	{
		return new PHPUnit_Extensions_Database_DataSet_YamlDataSet($this->fixture_path . 'assignments.yml');
	}
	public function testLoadUser()
	{
		$dataset = $this->getConnection()->createDataSet(array('users','assignments','steps'));
		$config = RPC_Config::get_instance(__DIR__ . '/fixtures/config.inc.php');
		$db = RPC_DB::get_connection($config);

		// Load user 1
		$user = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 1, $config, $db);

		$this->assertEquals('Test User 1', $user->name);

		$user->set_email('user1newemail@EXAMPLE.com');

		$user = new RPC_User(RPC_User::RPC_QUERY_USER_BY_ID, 1, $config, $db);
		$this->assertEquals('user1newemail@example.com', $user->email);
		
		// Should have 1 template
		$this->assertEquals(1, count(RPC_User::get_templates($user)));

		// Should have 2 assignments
		$this->assertEquals(2, count($user->get_assignments()));
	}
}
