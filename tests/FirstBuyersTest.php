<?php

require_once dirname(__FILE__) . '/StatsregTestCase.php';

class FirstBuyersTest extends StatsregTestCase
{
    public function testCountsDistinctValidOrdersInsideTwelveHourWindow()
    {
        Shop::setShareOrder(1, true);
        $employee = $this->setDateRange('2016-06-15', '2016-06-15');
        $module = $this->newModule();
        $this->applyEmployeeToModule($module, $employee);

        $this->insertCustomer(1, 1, 1, '2016-06-15 10:00:00');
        $this->insertGuest(1, 1);
        $this->insertConnection(1, 1, 1, '2016-06-15 06:01:00');
        $this->insertConnection(2, 1, 1, '2016-06-01 00:00:00');
        $this->insertOrder(1, 1, 1, '2016-06-15 18:00:00', 1);
        $this->insertOrder(2, 1, 1, '2016-06-15 19:00:00', 1);

        $this->insertCustomer(2, 1, 1, '2016-06-15 10:00:00');
        $this->insertGuest(2, 2);
        $this->insertConnection(3, 2, 1, '2016-06-15 06:00:00');
        $this->insertOrder(3, 2, 1, '2016-06-15 18:00:00', 1);

        $this->insertCustomer(3, 1, 1, '2016-06-15 10:00:00');
        $this->insertGuest(3, 3);
        $this->insertConnection(4, 3, 1, '2016-06-16 06:01:00');
        $this->insertOrder(4, 3, 1, '2016-06-15 18:00:00', 1);

        $this->insertCustomer(4, 1, 1, '2016-06-15 10:00:00');
        $this->insertGuest(4, 4);
        $this->insertConnection(5, 4, 1, '2016-06-15 17:00:00');
        $this->insertOrder(5, 4, 1, '2016-06-15 18:00:00', 0);

        $this->insertCustomer(5, 1, 1, '2016-06-14 10:00:00');
        $this->insertGuest(5, 5);
        $this->insertConnection(6, 5, 1, '2016-06-14 17:00:00');
        $this->insertOrder(6, 5, 1, '2016-06-14 18:00:00', 1);

        $this->insertCustomer(6, 1, 1, '2016-06-15 10:00:00');
        $this->insertOrder(7, 6, 1, '2016-06-15 18:00:00', 1);

        $this->insertCustomer(7, 2, 1, '2016-06-15 10:00:00');
        $this->insertGuest(7, 7);
        $this->insertConnection(8, 7, 2, '2016-06-15 17:00:00');
        $this->insertOrder(8, 7, 2, '2016-06-15 18:00:00', 1);

        $this->insertCustomer(8, 3, 2, '2016-06-15 10:00:00');
        $this->insertGuest(8, 8);
        $this->insertConnection(9, 8, 3, '2016-06-15 17:00:00');
        $this->insertOrder(9, 8, 3, '2016-06-15 18:00:00', 1);

        $this->assertCountValue(2, $module->getFirstBuyers());
    }

    public function testReturnsZeroWhenBuyerQueryFails()
    {
        $employee = $this->setDateRange('2016-06-15', '2016-06-15');
        $module = $this->newModule();
        $this->applyEmployeeToModule($module, $employee);
        statsreg_exec_sql('RENAME TABLE `' . _DB_PREFIX_ . 'orders` TO `' . _DB_PREFIX_ . 'orders_hidden`');
        $this->assertSame(0, (int) $module->getFirstBuyers());
    }
}
