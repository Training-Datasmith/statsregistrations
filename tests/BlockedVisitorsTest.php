<?php

require_once dirname(__FILE__) . '/StatsregTestCase.php';

class BlockedVisitorsTest extends StatsregTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->insertPageType(1, 'authentication');
        $this->insertPageType(2, 'product');
    }

    public function testCountsDistinctUnauthenticatedGuestsOnAuthenticationPage()
    {
        Shop::setShareCustomer(1, true);
        $employee = $this->setDateRange('2016-06-01', '2016-06-15');
        $module = $this->newModule();
        $this->applyEmployeeToModule($module, $employee);

        $this->insertGuest(1, null);
        $this->insertConnection(101, 1, 1, '2016-06-02 10:00:00');
        $this->insertPage(1001, 1);
        $this->insertConnectionPage(101, 1001);
        $this->insertConnection(102, 1, 1, '2016-06-03 10:00:00');
        $this->insertPage(1002, 1);
        $this->insertConnectionPage(102, 1002);
        $this->seedAuthenticationHit(2, 0, 1, 103, 1003, '2016-06-04 08:00:00');
        $this->seedAuthenticationHit(3, 9, 1, 104, 1004, '2016-06-04 09:00:00');
        $this->insertGuest(4, null);
        $this->insertConnection(105, 4, 1, '2016-06-04 10:00:00');
        $this->insertPage(1005, 2);
        $this->insertConnectionPage(105, 1005);
        $this->seedAuthenticationHit(5, null, 1, 106, 1006, '2016-05-01 10:00:00');
        $this->seedAuthenticationHit(6, null, 2, 107, 1007, '2016-06-04 11:00:00');

        $this->assertCountValue(2, $module->getBlockedVisitors());
    }

    public function testReturnsZeroWhenBlockedVisitorQueryFails()
    {
        $employee = $this->setDateRange('2016-06-01', '2016-06-15');
        $module = $this->newModule();
        $this->applyEmployeeToModule($module, $employee);
        statsreg_exec_sql('RENAME TABLE `' . _DB_PREFIX_ . 'page_type` TO `' . _DB_PREFIX_ . 'page_type_hidden`');
        $this->assertSame(0, (int) $module->getBlockedVisitors());
    }
}
