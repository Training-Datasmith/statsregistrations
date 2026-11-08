<?php

if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', '1.7.8.11');
}
if (!defined('_DB_PREFIX_')) {
    define('_DB_PREFIX_', 'psr_');
}
if (!defined('_PS_USE_SQL_SLAVE_')) {
    define('_PS_USE_SQL_SLAVE_', false);
}

class Employee
{
    public $stats_date_from;
    public $stats_date_to;

    public function __construct($from, $to)
    {
        $this->stats_date_from = $from;
        $this->stats_date_to = $to;
    }
}

class Module
{
    public $name;
    public $tab;
    public $version;
    public $author;
    public $need_instance;
    public $displayName;
    public $description;
    public $ps_versions_compliancy = array();

    public static $installResult = true;
    public static $registerHookResult = true;
    public static $hookCalls = array();
    public static $transLog = array();

    public function __construct()
    {
    }

    public function install()
    {
        return self::$installResult;
    }

    public function registerHook($name)
    {
        self::$hookCalls[] = array('register', $name);

        return self::$registerHookResult;
    }

    public function unregisterHook($name)
    {
        self::$hookCalls[] = array('unregister', $name);

        return true;
    }

    public function trans($id, array $parameters = array(), $domain = null, $locale = null)
    {
        self::$transLog[] = array($id, $parameters, $domain, $locale);

        return $id;
    }

    public static function resetModuleStatics()
    {
        self::$installResult = true;
        self::$registerHookResult = true;
        self::$hookCalls = array();
        self::$transLog = array();
    }
}

class ModuleGraph extends Module
{
    const SHARE_CUSTOMER = 'share_customer';
    const SHARE_ORDER = 'share_order';

    protected $_employee;
    protected $_values = array();
    protected $_legend = array();
    protected $_titles = array('main' => null, 'x' => null, 'y' => null);
    protected $_csv = '';

    public static $contextEmployee;
    public static $engineCalls = array();
    public static $csvExportCalls = array();

    public function setEmployee($id_employee)
    {
    }

    public function getDate()
    {
        return ModuleGraph::getDateBetween($this->_employee);
    }

    public static function getDateBetween($employee = null)
    {
        if ($employee === null) {
            $employee = self::$contextEmployee;
        }
        if (!($employee instanceof Employee)) {
            throw new RuntimeException('No employee for date range');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $employee->stats_date_from)
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $employee->stats_date_to)) {
            throw new RuntimeException('Invalid employee stats dates');
        }

        return ' \'' . $employee->stats_date_from . ' 00:00:00\' AND \'' . $employee->stats_date_to . ' 23:59:59\' ';
    }

    protected function setDateGraph($layers, $legend = false)
    {
        $fromArray = getdate(strtotime($this->_employee->stats_date_from));
        $toArray = getdate(strtotime($this->_employee->stats_date_to));

        if ($this->_employee->stats_date_from === $this->_employee->stats_date_to) {
            if ($legend) {
                for ($i = 0; $i < 24; ++$i) {
                    if ($layers == 1) {
                        $this->_values[$i] = 0;
                    } else {
                        for ($j = 0; $j < $layers; ++$j) {
                            $this->_values[$j][$i] = 0;
                        }
                    }
                    $this->_legend[$i] = ($i % 2) ? '' : sprintf('%02dh', $i);
                }
            }
            if (is_callable(array($this, 'setDayValues'))) {
                $this->setDayValues($layers);
            }
        } elseif (strtotime($this->_employee->stats_date_to) - strtotime($this->_employee->stats_date_from) <= 2678400) {
            if ($legend) {
                $days = array();
                if ($fromArray['mon'] == $toArray['mon']) {
                    for ($i = $fromArray['mday']; $i <= $toArray['mday']; ++$i) {
                        $days[] = $i;
                    }
                } else {
                    $imax = date('t', mktime(0, 0, 0, $fromArray['mon'], 1, $fromArray['year']));
                    for ($i = $fromArray['mday']; $i <= $imax; ++$i) {
                        $days[] = $i;
                    }
                    for ($i = 1; $i <= $toArray['mday']; ++$i) {
                        $days[] = $i;
                    }
                }
                foreach ($days as $i) {
                    if ($layers == 1) {
                        $this->_values[$i] = 0;
                    } else {
                        for ($j = 0; $j < $layers; ++$j) {
                            $this->_values[$j][$i] = 0;
                        }
                    }
                    $this->_legend[$i] = ($i % 2) ? '' : sprintf('%02d', $i);
                }
            }
            if (is_callable(array($this, 'setMonthValues'))) {
                $this->setMonthValues($layers);
            }
        } elseif (strtotime('-1 year', strtotime($this->_employee->stats_date_to)) < strtotime($this->_employee->stats_date_from)) {
            if ($legend) {
                $months = array();
                if ($fromArray['year'] == $toArray['year']) {
                    for ($i = $fromArray['mon']; $i <= $toArray['mon']; ++$i) {
                        $months[] = $i;
                    }
                } else {
                    for ($i = $fromArray['mon']; $i <= 12; ++$i) {
                        $months[] = $i;
                    }
                    for ($i = 1; $i <= $toArray['mon']; ++$i) {
                        $months[] = $i;
                    }
                }
                foreach ($months as $i) {
                    if ($layers == 1) {
                        $this->_values[$i] = 0;
                    } else {
                        for ($j = 0; $j < $layers; ++$j) {
                            $this->_values[$j][$i] = 0;
                        }
                    }
                    $this->_legend[$i] = sprintf('%02d', $i);
                }
            }
            if (is_callable(array($this, 'setYearValues'))) {
                $this->setYearValues($layers);
            }
        } else {
            if ($legend) {
                $years = array();
                for ($i = $fromArray['year']; $i <= $toArray['year']; ++$i) {
                    $years[] = $i;
                }
                foreach ($years as $i) {
                    if ($layers == 1) {
                        $this->_values[$i] = 0;
                    } else {
                        for ($j = 0; $j < $layers; ++$j) {
                            $this->_values[$j][$i] = 0;
                        }
                    }
                    $this->_legend[$i] = sprintf('%04d', $i);
                }
            }
            if (is_callable(array($this, 'setAllTimeValues'))) {
                $this->setAllTimeValues($layers);
            }
        }
    }

    protected function csvExport($datas)
    {
        self::$csvExportCalls[] = $datas;
        $layers = isset($datas['layers']) ? $datas['layers'] : 1;
        $type = isset($datas['type']) ? $datas['type'] : 'line';
        $this->getData($layers);
        $payload = array(
            'layers' => $layers,
            'type' => $type,
            'title' => isset($this->_titles['main']) ? $this->_titles['main'] : null,
            'values' => $this->_values,
        );
        echo json_encode($payload);
        exit(0);
    }

    public function engine($params)
    {
        self::$engineCalls[] = $params;
        $layers = isset($params['layers']) ? $params['layers'] : 1;
        $this->getData($layers);
        $parts = array();
        foreach ($this->_values as $key => $value) {
            $parts[] = $key . '=' . $value;
        }

        return 'GRAPH:' . implode(',', $parts);
    }

    public static function resetGraphStatics()
    {
        self::$contextEmployee = null;
        self::$engineCalls = array();
        self::$csvExportCalls = array();
    }
}

class Shop
{
    const SHARE_CUSTOMER = 'share_customer';
    const SHARE_ORDER = 'share_order';
    const CONTEXT_SHOP = 1;

    private static $context = self::CONTEXT_SHOP;
    private static $contextShopId = 1;
    private static $groups = array(
        1 => array(
            'share_customer' => false,
            'share_order' => false,
            'shops' => array(1 => true, 2 => true),
        ),
        2 => array(
            'share_customer' => false,
            'share_order' => false,
            'shops' => array(3 => true),
        ),
    );

    public static function resetShopStatics()
    {
        self::$context = self::CONTEXT_SHOP;
        self::$contextShopId = 1;
        self::$groups = array(
            1 => array(
                'share_customer' => false,
                'share_order' => false,
                'shops' => array(1 => true, 2 => true),
            ),
            2 => array(
                'share_customer' => false,
                'share_order' => false,
                'shops' => array(3 => true),
            ),
        );
    }

    public static function setShareCustomer($groupId, $value)
    {
        self::$groups[$groupId]['share_customer'] = (bool) $value;
    }

    public static function setShareOrder($groupId, $value)
    {
        self::$groups[$groupId]['share_order'] = (bool) $value;
    }

    public static function getContext()
    {
        return self::$context;
    }

    public static function getContextShopID()
    {
        return self::$contextShopId;
    }

    public static function setContextShopId($id)
    {
        self::$contextShopId = (int) $id;
    }

    private static function getGroupFromShop($shopId)
    {
        foreach (self::$groups as $groupId => $group) {
            if (isset($group['shops'][$shopId])) {
                return array_merge($group, array('id' => $groupId));
            }
        }

        return false;
    }

    public static function getContextListShopID($share = false)
    {
        $shopId = self::$contextShopId;
        if ($share) {
            foreach (self::$groups as $group) {
                if (isset($group['shops'][$shopId]) && !empty($group[$share])) {
                    $ids = array_keys($group['shops']);
                    sort($ids, SORT_NUMERIC);

                    return $ids;
                }
            }
        }
        $ids = array($shopId);
        sort($ids, SORT_NUMERIC);

        return $ids;
    }

    public static function getContextShopGroupID()
    {
        $group = self::getGroupFromShop(self::$contextShopId);

        return $group ? (int) $group['id'] : 1;
    }

    public static function addSqlRestriction($share = false, $alias = null)
    {
        if ($alias) {
            $alias .= '.';
        }
        $group = self::getGroupFromShop(self::getContextShopID());
        if ($share == self::SHARE_CUSTOMER
            && self::getContext() == self::CONTEXT_SHOP
            && $group
            && !empty($group['share_customer'])) {
            return ' AND ' . $alias . 'id_shop_group = ' . (int) self::getContextShopGroupID() . ' ';
        }
        $ids = self::getContextListShopID($share);

        return ' AND ' . $alias . 'id_shop IN (' . implode(', ', $ids) . ') ';
    }
}

class Db
{
    private static $instance;
    private $link;

    private function __construct()
    {
        $host = getenv('STATSREG_DB_HOST');
        if ($host === false || $host === '') {
            $host = '127.0.0.1';
        }
        $port = getenv('STATSREG_DB_PORT');
        if ($port === false || $port === '') {
            $port = 3306;
        }
        $user = getenv('STATSREG_DB_USER');
        if ($user === false || $user === '') {
            $user = 'statsreg';
        }
        $password = getenv('STATSREG_DB_PASSWORD');
        if ($password === false) {
            $password = 'statsreg';
        }
        $name = getenv('STATSREG_DB_NAME');
        if ($name === false || $name === '') {
            $name = 'statsreg_test';
        }

        mysqli_report(MYSQLI_REPORT_OFF);
        $this->link = mysqli_connect($host, $user, $password, $name, (int) $port);
        if (!$this->link) {
            throw new RuntimeException('Database connection failed: ' . mysqli_connect_error());
        }
        if (!mysqli_set_charset($this->link, 'utf8mb4')) {
            throw new RuntimeException('Failed to set charset');
        }
        mysqli_query($this->link, "SET SESSION sql_mode=''");
        mysqli_query($this->link, "SET time_zone = '+00:00'");
    }

    public static function getInstance($slave = false)
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function resetInstance()
    {
        if (self::$instance) {
            self::$instance = null;
        }
    }

    public function query($sql)
    {
        return mysqli_query($this->link, $sql);
    }

    public function getRow($sql)
    {
        $result = $this->query($sql);
        if ($result === false) {
            return false;
        }
        $row = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        if ($row === null) {
            return false;
        }

        return $row;
    }

    public function executeS($sql)
    {
        $result = $this->query($sql);
        if ($result === false) {
            return false;
        }
        $rows = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        mysqli_free_result($result);

        return $rows;
    }

    public function getLink()
    {
        return $this->link;
    }
}

class Tools
{
    public static function getValue($key, $default_value = false)
    {
        if (!isset($key) || empty($key) || !is_string($key)) {
            return false;
        }
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }

        return $default_value;
    }

    public static function safeOutput($string, $html = false)
    {
        if (!$html) {
            $string = strip_tags($string);
        }

        return htmlentities((string) $string, ENT_QUOTES, 'UTF-8');
    }

    public static function substr($str, $start, $length = false)
    {
        if (is_array($str)) {
            return false;
        }
        if ($length === false) {
            return substr($str, $start);
        }

        return substr($str, $start, (int) $length);
    }
}

function statsreg_reset_schema()
{
    $db = Db::getInstance();
    $link = $db->getLink();
    $prefix = _DB_PREFIX_;
    $tables = array(
        'connections_page',
        'connections',
        'page',
        'page_type',
        'guest',
        'orders',
        'customer',
    );
    $hidden = array();
    foreach ($tables as $table) {
        $hidden[] = $prefix . $table . '_hidden';
    }
    mysqli_query($link, 'SET FOREIGN_KEY_CHECKS=0');
    $result = mysqli_query($link, 'SHOW TABLES');
    if ($result) {
        while ($row = mysqli_fetch_row($result)) {
            $name = $row[0];
            if (strpos($name, $prefix) === 0) {
                mysqli_query($link, 'DROP TABLE IF EXISTS `' . $name . '`');
            }
        }
        mysqli_free_result($result);
    }
    foreach ($hidden as $name) {
        mysqli_query($link, 'DROP TABLE IF EXISTS `' . $name . '`');
    }
    mysqli_query($link, 'SET FOREIGN_KEY_CHECKS=1');

    $ddl = array(
        "CREATE TABLE `{$prefix}customer` (
            `id_customer` INT NOT NULL PRIMARY KEY,
            `id_shop` INT NOT NULL,
            `id_shop_group` INT NOT NULL,
            `date_add` DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE `{$prefix}orders` (
            `id_order` INT NOT NULL PRIMARY KEY,
            `id_customer` INT NOT NULL,
            `id_shop` INT NOT NULL,
            `date_add` DATETIME NOT NULL,
            `valid` INT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE `{$prefix}guest` (
            `id_guest` INT NOT NULL PRIMARY KEY,
            `id_customer` INT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE `{$prefix}connections` (
            `id_connections` INT NOT NULL PRIMARY KEY,
            `id_guest` INT NOT NULL,
            `id_shop` INT NOT NULL,
            `date_add` DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE `{$prefix}connections_page` (
            `id_connections` INT NOT NULL,
            `id_page` INT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE `{$prefix}page` (
            `id_page` INT NOT NULL PRIMARY KEY,
            `id_page_type` INT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE `{$prefix}page_type` (
            `id_page_type` INT NOT NULL PRIMARY KEY,
            `name` VARCHAR(64) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    );
    foreach ($ddl as $sql) {
        if (!$db->query($sql)) {
            throw new RuntimeException('Schema creation failed');
        }
    }
}

function statsreg_exec_sql($sql)
{
  $db = Db::getInstance();
  if (!$db->query($sql)) {
    throw new RuntimeException('SQL failed: ' . $sql);
  }
}
