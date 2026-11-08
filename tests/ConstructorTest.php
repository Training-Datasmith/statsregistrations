<?php

require_once dirname(__FILE__) . '/StatsregTestCase.php';

class ConstructorTest extends StatsregTestCase
{
    public function testModuleIdentity()
    {
        $module = $this->newModule();
        $this->assertSame('statsregistrations', $module->name);
        $this->assertSame('2.1.0', $module->version);
        $this->assertSame('PrestaShop', $module->author);
        $this->assertSame(0, $module->need_instance);
        $this->assertSame('1.7.1.0', $module->ps_versions_compliancy['min']);
        $this->assertInternalType('string', _PS_VERSION_);
        $this->assertNotEmpty(_PS_VERSION_);
        $this->assertSame(_PS_VERSION_, $module->ps_versions_compliancy['max']);
    }

    public function testTranslatedDisplayNameAndDescription()
    {
        $module = $this->newModule();
        $this->assertSame('Customer accounts', $module->displayName);
        $this->assertSame('Adds a registration progress tab to the Stats dashboard.', $module->description);

        $expected = array(
            array('Customer accounts', array(), 'Modules.Statsregistrations.Admin', null),
            array(
                'Adds a registration progress tab to the Stats dashboard.',
                array(),
                'Modules.Statsregistrations.Admin',
                null,
            ),
        );
        $this->assertSame($expected, Module::$transLog);
    }
}
