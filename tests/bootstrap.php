<?php

date_default_timezone_set('UTC');
setlocale(LC_ALL, 'C');

$testsDir = dirname(__FILE__);
$moduleDir = dirname($testsDir);

require_once $testsDir . '/Harness.php';

require_once $moduleDir . '/statsregistrations.php';

class StatsRegistrationsUnderTest extends statsregistrations
{
    public function setStatsEmployee(Employee $employee)
    {
        $this->_employee = $employee;
        ModuleGraph::$contextEmployee = $employee;
    }

    public function getValuesForTest()
    {
        return $this->_values;
    }

    public function getTitlesForTest()
    {
        return $this->_titles;
    }

    public function getLegendForTest()
    {
        return $this->_legend;
    }

    public function callGetData($layers)
    {
        $this->getData($layers);
    }
}
