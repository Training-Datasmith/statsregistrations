<?php

abstract class StatsregTestCase extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        Module::resetModuleStatics();
        ModuleGraph::resetGraphStatics();
        Shop::resetShopStatics();
        Db::resetInstance();
        Shop::setContextShopId(1);
        Shop::setShareCustomer(1, false);
        Shop::setShareOrder(1, false);
        Shop::setShareCustomer(2, false);
        Shop::setShareOrder(2, false);

        $_GET = array();
        $_POST = array();
        $_REQUEST = array();
        $_SERVER['REQUEST_URI'] = '/bo/index.php?controller=AdminStats&module=statsregistrations';

        statsreg_reset_schema();
    }

    protected function tearDown()
    {
        statsreg_reset_schema();
        Db::resetInstance();
    }

    protected function newModule()
    {
        return new StatsRegistrationsUnderTest();
    }

    protected function setDateRange($from, $to)
    {
        $employee = new Employee($from, $to);
        ModuleGraph::$contextEmployee = $employee;

        return $employee;
    }

    protected function applyEmployeeToModule(StatsRegistrationsUnderTest $module, Employee $employee)
    {
        $module->setStatsEmployee($employee);
    }

    protected function assertCountValue($expected, $actual)
    {
        if (is_int($actual)) {
            $normalized = $actual;
        } elseif (is_string($actual) && ctype_digit($actual)) {
            $normalized = (int) $actual;
        } else {
            $this->fail('Count result must be int or numeric string');
        }
        $this->assertSame($expected, $normalized);
    }

    protected function insertCustomer($id, $shop, $group, $dateAdd)
    {
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'customer` (`id_customer`, `id_shop`, `id_shop_group`, `date_add`) VALUES ('
            . (int) $id . ', ' . (int) $shop . ', ' . (int) $group . ", '" . $dateAdd . "')";
        statsreg_exec_sql($sql);
    }

    protected function insertPageType($id, $name)
    {
        $escaped = mysqli_real_escape_string(Db::getInstance()->getLink(), $name);
        statsreg_exec_sql(
            'INSERT INTO `' . _DB_PREFIX_ . 'page_type` (`id_page_type`, `name`) VALUES (' . (int) $id . ", '" . $escaped . "')"
        );
    }

    protected function insertPage($id, $pageType)
    {
        statsreg_exec_sql(
            'INSERT INTO `' . _DB_PREFIX_ . 'page` (`id_page`, `id_page_type`) VALUES (' . (int) $id . ', ' . (int) $pageType . ')'
        );
    }

    protected function insertGuest($id, $idCustomer)
    {
        if ($idCustomer === null) {
            $val = 'NULL';
        } else {
            $val = (int) $idCustomer;
        }
        statsreg_exec_sql('INSERT INTO `' . _DB_PREFIX_ . 'guest` (`id_guest`, `id_customer`) VALUES (' . (int) $id . ', ' . $val . ')');
    }

    protected function insertConnection($idConn, $guest, $shop, $dateAdd)
    {
        statsreg_exec_sql(
            'INSERT INTO `' . _DB_PREFIX_ . 'connections` (`id_connections`, `id_guest`, `id_shop`, `date_add`) VALUES ('
            . (int) $idConn . ', ' . (int) $guest . ', ' . (int) $shop . ", '" . $dateAdd . "')"
        );
    }

    protected function insertConnectionPage($conn, $page)
    {
        statsreg_exec_sql(
            'INSERT INTO `' . _DB_PREFIX_ . 'connections_page` (`id_connections`, `id_page`) VALUES (' . (int) $conn . ', ' . (int) $page . ')'
        );
    }

    protected function insertOrder($id, $customer, $shop, $dateAdd, $valid)
    {
        statsreg_exec_sql(
            'INSERT INTO `' . _DB_PREFIX_ . 'orders` (`id_order`, `id_customer`, `id_shop`, `date_add`, `valid`) VALUES ('
            . (int) $id . ', ' . (int) $customer . ', ' . (int) $shop . ", '" . $dateAdd . "', " . (int) $valid . ')'
        );
    }

    protected function seedAuthenticationHit($guestId, $idCustomer, $shop, $connId, $pageId, $dateAdd, $pageTypeId = 1)
    {
        $this->insertGuest($guestId, $idCustomer);
        $this->insertConnection($connId, $guestId, $shop, $dateAdd);
        $this->insertPage($pageId, $pageTypeId);
        $this->insertConnectionPage($connId, $pageId);
    }

    protected function assertNoPhpDiagnostics($text)
    {
        $needles = array(
            'Notice:',
            'Warning:',
            'Deprecated:',
            'Strict Standards:',
            'Parse error:',
            'Fatal error:',
        );
        foreach ($needles as $needle) {
            $this->assertNotContains($needle, $text);
        }
    }
}
