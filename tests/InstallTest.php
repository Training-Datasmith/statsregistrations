<?php

require_once dirname(__FILE__) . '/StatsregTestCase.php';

class InstallTest extends StatsregTestCase
{
    public function testInstallRegistersDisplayAdminStatsModulesHook()
    {
        Module::$installResult = true;
        Module::$registerHookResult = true;
        $module = $this->newModule();
        $this->assertTrue($module->install());
        $this->assertSame(array(array('register', 'displayAdminStatsModules')), Module::$hookCalls);
    }

    public function testInstallReturnsFalseWithoutRegisteringHookWhenParentInstallFails()
    {
        Module::$installResult = false;
        $module = $this->newModule();
        $this->assertFalse($module->install());
        $this->assertSame(array(), Module::$hookCalls);
    }

    public function testInstallReturnsFalseWhenHookRegistrationFails()
    {
        Module::$installResult = true;
        Module::$registerHookResult = false;
        $module = $this->newModule();
        $this->assertFalse($module->install());
        $this->assertSame(array(array('register', 'displayAdminStatsModules')), Module::$hookCalls);
    }
}
