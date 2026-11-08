<?php

require_once dirname(__FILE__) . '/StatsregTestCase.php';

class ExportTest extends StatsregTestCase
{
    public function testExportEmitsInitializedSeriesForLineGraph()
    {
        $employee = $this->setDateRange('2016-06-15', '2016-06-15');
        $module = $this->newModule();
        $this->applyEmployeeToModule($module, $employee);

        $this->insertCustomer(1, 1, 1, '2016-06-15 13:45:00');
        $this->insertCustomer(2, 1, 1, '2016-06-15 13:01:00');
        $this->insertCustomer(3, 1, 1, '2016-06-15 00:15:00');
        $this->insertCustomer(4, 2, 1, '2016-06-15 13:00:00');

        $wrapper = tempnam(sys_get_temp_dir(), 'statsreg-export-');
        $php = PHP_BINARY;
        $bootstrap = dirname(__FILE__) . '/bootstrap.php';
        $code = '<?php
require ' . var_export($bootstrap, true) . ';
$employee = new Employee("2016-06-15", "2016-06-15");
$module = new StatsRegistrationsUnderTest();
$module->setStatsEmployee($employee);
$_GET["export"] = "1";
$module->hookDisplayAdminStatsModules();
echo "SENTINEL\n";
';
        file_put_contents($wrapper, $code);

        $descriptors = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );
        $cmd = escapeshellarg($php) . ' -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 -d log_errors=0 '
            . escapeshellarg($wrapper);
        $proc = proc_open($cmd, $descriptors, $pipes);
        $this->assertInternalType('resource', $proc);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($proc);
        unlink($wrapper);

        $this->assertSame(0, $exit);
        $combined = $stdout . $stderr;
        $this->assertNoPhpDiagnostics($combined);
        $this->assertNotContains('SENTINEL', $stdout);
        $this->assertNotContains('panel-heading', $stdout);

        $payload = json_decode(trim($stdout), true);
        $this->assertInternalType('array', $payload);
        $this->assertSame(1, $payload['layers']);
        $this->assertSame('line', $payload['type']);
        $this->assertSame('Number of customer accounts created', $payload['title']);
        $values = $payload['values'];
        $this->assertArrayHasKey(13, $values);
        $this->assertArrayHasKey(0, $values);
        $this->assertArrayHasKey(7, $values);
        $this->assertSame(2, $values[13]);
        $this->assertSame(1, $values[0]);
        $this->assertSame(0, $values[7]);
        $this->assertSame(24, count($values));
    }
}
