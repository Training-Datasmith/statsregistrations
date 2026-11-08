<?php

require_once dirname(__FILE__) . '/StatsregTestCase.php';

class GraphValuesTest extends StatsregTestCase
{
    public function testDayRangeBucketsHours()
    {
        $employee = $this->setDateRange('2016-06-15', '2016-06-15');
        $module = $this->newModule();
        $this->applyEmployeeToModule($module, $employee);

        $this->insertCustomer(1, 1, 1, '2016-06-15 13:45:00');
        $this->insertCustomer(2, 1, 1, '2016-06-15 13:01:00');
        $this->insertCustomer(3, 1, 1, '2016-06-15 00:15:00');
        $this->insertCustomer(4, 2, 1, '2016-06-15 13:00:00');
        $this->insertCustomer(5, 1, 1, '2016-06-16 13:00:00');

        $module->callGetData(1);
        $values = $module->getValuesForTest();
        $this->assertSame(2, $values[13]);
        $this->assertSame(1, $values[0]);
        $this->assertSame(0, $values[8]);
        $this->assertSame(24, count($values));
        $titles = $module->getTitlesForTest();
        $this->assertSame('Number of customer accounts created', $titles['main']);
        $this->assertTrue($this->transLogContains('Number of customer accounts created'));
    }

    public function testMonthRangeBucketsDays()
    {
        $employee = $this->setDateRange('2016-06-01', '2016-06-15');
        $module = $this->newModule();
        $this->applyEmployeeToModule($module, $employee);

        $this->insertCustomer(1, 1, 1, '2016-06-01 10:00:00');
        $this->insertCustomer(2, 1, 1, '2016-06-01 11:00:00');
        $this->insertCustomer(3, 1, 1, '2016-06-15 12:00:00');
        $this->insertCustomer(4, 1, 1, '2016-06-16 12:00:00');
        $this->insertCustomer(5, 2, 1, '2016-06-02 12:00:00');

        $module->callGetData(1);
        $values = $module->getValuesForTest();
        $this->assertSame(2, $values[1]);
        $this->assertSame(1, $values[15]);
        $this->assertSame(0, $values[2]);
        $this->assertSame(15, count($values));
        $this->assertArrayNotHasKey(16, $values);
    }

    public function testYearRangeBucketsMonths()
    {
        $employee = $this->setDateRange('2016-01-01', '2016-08-31');
        $module = $this->newModule();
        $this->applyEmployeeToModule($module, $employee);

        $this->insertCustomer(1, 1, 1, '2016-03-01 10:00:00');
        $this->insertCustomer(2, 1, 1, '2016-03-02 10:00:00');
        $this->insertCustomer(3, 1, 1, '2016-08-31 23:00:00');
        $this->insertCustomer(4, 1, 1, '2016-09-01 10:00:00');
        $this->insertCustomer(5, 2, 1, '2016-03-03 10:00:00');

        $module->callGetData(1);
        $values = $module->getValuesForTest();
        $this->assertSame(2, $values[3]);
        $this->assertSame(1, $values[8]);
        $this->assertSame(0, $values[1]);
        $this->assertSame(8, count($values));
        $this->assertArrayNotHasKey(9, $values);
    }

    public function testMultiYearRangeBucketsYears()
    {
        $employee = $this->setDateRange('2014-03-01', '2016-06-01');
        $module = $this->newModule();
        $this->applyEmployeeToModule($module, $employee);

        $this->insertCustomer(1, 1, 1, '2014-06-01 10:00:00');
        $this->insertCustomer(2, 1, 1, '2016-01-15 10:00:00');
        $this->insertCustomer(3, 1, 1, '2016-06-01 10:00:00');
        $this->insertCustomer(4, 1, 1, '2016-06-02 10:00:00');
        $this->insertCustomer(5, 2, 1, '2016-02-01 10:00:00');

        $module->callGetData(1);
        $values = $module->getValuesForTest();
        $this->assertSame(1, $values[2014]);
        $this->assertSame(0, $values[2015]);
        $this->assertSame(2, $values[2016]);
        $this->assertSame(3, count($values));
    }

    public function testSharedCustomerGroupCountsSiblingShopOnGraph()
    {
        Shop::setShareCustomer(1, true);
        $employee = $this->setDateRange('2016-06-01', '2016-06-10');
        $module = $this->newModule();
        $this->applyEmployeeToModule($module, $employee);

        $this->insertCustomer(1, 1, 1, '2016-06-04 10:00:00');
        $this->insertCustomer(2, 2, 1, '2016-06-04 11:00:00');
        $this->insertCustomer(3, 2, 2, '2016-06-04 12:00:00');
        $this->insertCustomer(4, 1, 1, '2016-06-11 10:00:00');

        $module->callGetData(1);
        $values = $module->getValuesForTest();
        $this->assertSame(2, $values[4]);
        $this->assertSame(0, $values[5]);
        $this->assertSame(10, count($values));
    }

    private function transLogContains($id)
    {
        foreach (Module::$transLog as $entry) {
            if ($entry[0] === $id && $entry[2] === 'Modules.Statsregistrations.Admin') {
                return true;
            }
        }

        return false;
    }
}
