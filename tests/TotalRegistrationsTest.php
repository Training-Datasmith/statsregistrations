<?php

require_once dirname(__FILE__) . '/StatsregTestCase.php';

class TotalRegistrationsTest extends StatsregTestCase
{
    public function testCountsCustomersInsideInclusiveRangeForCurrentShop()
    {
        $employee = $this->setDateRange('2016-06-01', '2016-06-15');
        $module = $this->newModule();
        $this->applyEmployeeToModule($module, $employee);

        $this->insertCustomer(1, 1, 1, '2016-06-01 00:00:00');
        $this->insertCustomer(2, 1, 1, '2016-06-10 12:00:00');
        $this->insertCustomer(3, 1, 1, '2016-06-15 23:59:59');
        $this->insertCustomer(4, 1, 1, '2016-05-31 23:59:59');
        $this->insertCustomer(5, 1, 1, '2016-06-16 00:00:00');
        $this->insertCustomer(6, 2, 1, '2016-06-10 12:00:00');

        $this->assertCountValue(3, $module->getTotalRegistrations());
    }

    public function testReturnsZeroWhenCustomerQueryFails()
    {
        $employee = $this->setDateRange('2016-06-01', '2016-06-15');
        $module = $this->newModule();
        $this->applyEmployeeToModule($module, $employee);
        statsreg_exec_sql('RENAME TABLE `' . _DB_PREFIX_ . 'customer` TO `' . _DB_PREFIX_ . 'customer_hidden`');
        $value = $module->getTotalRegistrations();
        $this->assertInternalType('integer', $value);
        $this->assertSame(0, $value);
    }
}
