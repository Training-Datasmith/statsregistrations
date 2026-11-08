<?php

require_once dirname(__FILE__) . '/StatsregTestCase.php';

class RecordingUpgradeModule
{
    public $calls = array();

    public function unregisterHook($name)
    {
        $this->calls[] = array('unregister', $name);
    }

    public function registerHook($name)
    {
        $this->calls[] = array('register', $name);
    }
}

class UpgradeTest extends StatsregTestCase
{
    public function testUpgrade2001MovesAdminStatsHook()
    {
        require_once dirname(dirname(__FILE__)) . '/upgrade/upgrade-2.0.1.php';
        $module = new RecordingUpgradeModule();
        $this->assertTrue(upgrade_module_2_0_1($module));
        $this->assertSame(
            array(
                array('unregister', 'AdminStatsModules'),
                array('register', 'displayAdminStatsModules'),
            ),
            $module->calls
        );
    }
}
