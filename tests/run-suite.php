<?php

$expectedSha = 'ab8bc3017a64cf75a2112f5e089a1d5b0c4cc2116556d60c1af5e3f584e85c5e';
$phar = getenv('PHPUNIT_PHAR');
if ($phar === false || $phar === '') {
    fwrite(STDERR, "PHPUNIT_PHAR is required\n");
    exit(1);
}
if (!is_file($phar)) {
    fwrite(STDERR, "PHPUnit PHAR not found\n");
    exit(1);
}
$actualSha = hash_file('sha256', $phar);
if ($actualSha !== $expectedSha) {
    fwrite(STDERR, "PHPUnit PHAR SHA-256 mismatch\n");
    exit(1);
}

$testsDir = dirname(__FILE__);
$expectedFiles = array(
    'BlockedVisitorsTest.php',
    'ConfigXmlTest.php',
    'ConstructorTest.php',
    'ExportTest.php',
    'FirstBuyersTest.php',
    'GraphValuesTest.php',
    'GuardScriptsTest.php',
    'HookDisplayTest.php',
    'InstallTest.php',
    'TotalRegistrationsTest.php',
    'UpgradeTest.php',
);
$found = glob($testsDir . '/*Test.php');
$basenames = array();
foreach ($found as $path) {
    $basenames[] = basename($path);
}
sort($basenames);
if ($basenames !== $expectedFiles) {
    fwrite(STDERR, "Test file list drift\n");
    exit(1);
}

$paths = array();
foreach ($expectedFiles as $base) {
    $paths[] = $testsDir . '/' . $base;
}
srand(20261108);
shuffle($paths);

$tmpDir = sys_get_temp_dir() . '/statsreg-phpunit-' . getmypid() . '-' . mt_rand();
if (!mkdir($tmpDir, 0700, true) && !is_dir($tmpDir)) {
    fwrite(STDERR, "Failed to create temp dir\n");
    exit(1);
}
$xmlPath = $tmpDir . '/phpunit.xml';
$junitPath = $tmpDir . '/junit.xml';
$bootstrap = $testsDir . '/bootstrap.php';

$fileNodes = '';
foreach ($paths as $path) {
    $fileNodes .= '      <file>' . htmlspecialchars($path, ENT_XML1) . "</file>\n";
}

$xml = '<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="' . htmlspecialchars($bootstrap, ENT_XML1) . '"
         backupGlobals="false"
         backupStaticAttributes="false"
         colors="true"
         convertErrorsToExceptions="true"
         convertNoticesToExceptions="true"
         convertWarningsToExceptions="true"
         strict="true"
         processIsolation="false">
  <testsuites>
    <testsuite name="module-tests">
' . $fileNodes . '    </testsuite>
  </testsuites>
</phpunit>
';
file_put_contents($xmlPath, $xml);

$cmd = escapeshellarg(PHP_BINARY) . ' -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 '
    . escapeshellarg($phar) . ' --configuration ' . escapeshellarg($xmlPath)
    . ' --log-junit ' . escapeshellarg($junitPath);

$output = array();
$exit = 0;
exec($cmd . ' 2>&1', $output, $exit);
$text = implode("\n", $output);
fwrite(STDOUT, $text . "\n");

$failed = false;
if ($exit !== 0) {
    $failed = true;
}
$filteredLines = array();
foreach (explode("\n", $text) as $line) {
    if ($line === "Warning:\tDeprecated configuration setting \"strict\" used") {
        continue;
    }
    $filteredLines[] = $line;
}
$textForScan = implode("\n", $filteredLines);

$needles = array(
    'Notice:',
    'Deprecated:',
    'Strict Standards:',
    'Parse error:',
    'Fatal error:',
    'OK, but',
);
foreach ($needles as $needle) {
    if (strpos($textForScan, $needle) !== false) {
        $failed = true;
    }
}
if (strpos($textForScan, 'Warning:') !== false) {
    $failed = true;
}
if (preg_match('/Failures: (\d+)/', $text, $m) && (int) $m[1] > 0) {
    $failed = true;
}
if (preg_match('/Errors: (\d+)/', $text, $m) && (int) $m[1] > 0) {
    $failed = true;
}
$okLineFound = false;
foreach (explode("\n", $text) as $line) {
    if (preg_match('/^OK \(27 tests, [0-9]+ assertions\)$/', $line)) {
        $okLineFound = true;
        break;
    }
}
if (!$okLineFound) {
    $failed = true;
}
if (!is_file($junitPath)) {
    $failed = true;
} else {
    $junit = file_get_contents($junitPath);
    if (!preg_match('/testsuite name="module-tests"[^>]*tests="27"/', $junit)
        || !preg_match('/testsuite name="module-tests"[^>]*failures="0"/', $junit)
        || !preg_match('/testsuite name="module-tests"[^>]*errors="0"/', $junit)) {
        $failed = true;
    }
    if (preg_match('/testsuite name="module-tests"[^>]*skipped="([1-9][0-9]*)"/', $junit)) {
        $failed = true;
    }
}

$delete = array($xmlPath, $junitPath);
foreach ($delete as $file) {
    if (is_file($file)) {
        unlink($file);
    }
}
if (is_dir($tmpDir)) {
    rmdir($tmpDir);
}

exit($failed ? 1 : 0);
