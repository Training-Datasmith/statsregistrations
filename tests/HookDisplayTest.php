<?php

require_once dirname(__FILE__) . '/StatsregTestCase.php';

class HookDisplayTest extends StatsregTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->insertPageType(1, 'authentication');
    }

    public function testRendersDistinctPercentagesAndGraphSeries()
    {
        $employee = $this->setDateRange('2016-06-01', '2016-06-15');
        $module = $this->newModule();
        $this->applyEmployeeToModule($module, $employee);
        $_SERVER['REQUEST_URI'] = "/bo/index.php?controller=AdminStats&module=statsregistrations&token=a'b\"c";

        $this->insertCustomer(10, 1, 1, '2016-06-02 10:00:00');
        $this->insertCustomer(11, 1, 1, '2016-06-03 11:00:00');
        $this->insertCustomer(12, 1, 1, '2016-06-03 16:00:00');
        $this->insertCustomer(13, 2, 1, '2016-06-02 10:00:00');
        $this->insertCustomer(14, 1, 1, '2016-05-01 10:00:00');

        $this->seedAuthenticationHit(1, null, 1, 101, 1001, '2016-06-02 10:00:00');
        $this->seedAuthenticationHit(2, 10, 1, 102, 1002, '2016-06-02 11:00:00');

        $this->insertGuest(10, 10);
        $this->insertConnection(201, 10, 1, '2016-06-02 11:00:00');
        $this->insertOrder(301, 10, 1, '2016-06-02 12:00:00', 1);

        $this->insertGuest(11, 11);
        $this->insertConnection(202, 11, 1, '2016-06-02 00:00:00');
        $this->insertOrder(302, 11, 1, '2016-06-03 12:00:00', 1);

        $html = $module->hookDisplayAdminStatsModules();
        $this->assertContains('1 (25.00%)', $html);
        $this->assertContains('1 (33.33%)', $html);
        $this->assertContains('>3<', $html);
        $this->assertContains('The total number of accounts created is not in itself important information.', $html);
        $this->assertContains('CSV Export', $html);
        $expectedHref = "/bo/index.php?controller=AdminStats&amp;module=statsregistrations&amp;token=a&#039;b&quot;c&amp;export=1";
        $this->assertContains('href="' . $expectedHref . '"', $html);
        $this->assertSame(array(array('type' => 'line')), ModuleGraph::$engineCalls);
        $this->assertContains('GRAPH:', $html);

        $graphPart = substr($html, strpos($html, 'GRAPH:'));
        $tokens = explode(',', substr($graphPart, 6));
        $tokenMap = array();
        foreach ($tokens as $token) {
            if (strpos($token, '=') !== false) {
                list($k, $v) = explode('=', $token, 2);
                $tokenMap[$k] = $v;
            }
        }
        $this->assertSame('1', $tokenMap['2']);
        $this->assertSame('2', $tokenMap['3']);
        $this->assertSame('0', $tokenMap['4']);
    }

    public function testShowsCountsWhenThereAreNoRegistrations()
    {
        $employee = $this->setDateRange('2016-06-01', '2016-06-15');
        $module = $this->newModule();
        $this->applyEmployeeToModule($module, $employee);

        $this->seedAuthenticationHit(1, null, 1, 101, 1001, '2016-06-02 10:00:00');
        $this->seedAuthenticationHit(2, null, 1, 102, 1002, '2016-06-03 10:00:00');

        $html = $module->hookDisplayAdminStatsModules();
        $bodies = $this->extractStatSpanBodies($html);
        $this->assertSame(3, count($bodies));
        $this->assertRegExp('/^2( \([0-9]+\.[0-9]{2}%\))?$/', $bodies[0]);
        $this->assertRegExp('/^0( \([0-9]+\.[0-9]{2}%\))?$/', $bodies[1]);
        $this->assertRegExp('/^0( \([0-9]+\.[0-9]{2}%\))?$/', $bodies[2]);
    }

    public function testShowsZeroPercentsWhenRegistrationsExistButRatesAreZero()
    {
        $employee = $this->setDateRange('2016-06-01', '2016-06-15');
        $module = $this->newModule();
        $this->applyEmployeeToModule($module, $employee);

        $this->insertCustomer(1, 1, 1, '2016-06-02 10:00:00');
        $this->insertCustomer(2, 1, 1, '2016-06-03 11:00:00');
        $this->insertCustomer(3, 1, 1, '2016-06-04 12:00:00');

        $html = $module->hookDisplayAdminStatsModules();
        $this->assertSame(2, substr_count($html, '>0 (0.00%)<'));
        $this->assertContains('>3<', $html);
    }

    private function extractStatSpanBodies($html)
    {
        $pattern = '/<span class="totalStats">([^<]*)<\/span>/';
        preg_match_all($pattern, $html, $matches);

        return $matches[1];
    }
}
