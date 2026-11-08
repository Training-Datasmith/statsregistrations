<?php

require_once dirname(__FILE__) . '/StatsregTestCase.php';

class ConfigXmlTest extends StatsregTestCase
{
    public function testManifestNameAndVersionMatchModule()
    {
        $path = dirname(dirname(__FILE__)) . '/config.xml';
        $xml = file_get_contents($path);
        $this->assertInternalType('string', $xml);
        $this->assertInternalType('int', strpos($xml, '<name>statsregistrations</name>'));
        $this->assertInternalType('int', strpos($xml, '<version><![CDATA[2.1.0]]></version>'));
        $this->assertInternalType('int', strpos($xml, '<need_instance>0</need_instance>'));

        $module = $this->newModule();
        $this->assertSame('2.1.0', $module->version);
    }
}
