<?php

require_once dirname(__FILE__) . '/StatsregTestCase.php';

class GuardScriptsTest extends StatsregTestCase
{
    public function testModuleFileExitsCleanlyWhenPsVersionIsUndefined()
    {
        $path = dirname(dirname(__FILE__)) . '/statsregistrations.php';
        $this->assertTrue(is_file($path));
        $real = realpath($path);
        $this->assertInternalType('string', $real);

        $php = PHP_BINARY;
        $cmd = escapeshellarg($php) . ' -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 -d log_errors=0 '
            . escapeshellarg($real);
        $descriptors = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );
        $proc = proc_open($cmd, $descriptors, $pipes);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($proc);

        $this->assertSame(0, $exit);
        $this->assertSame('', $stdout);
        $this->assertSame('', $stderr);
        $this->assertNoPhpDiagnostics($stdout . $stderr);
    }

    /**
     * @dataProvider directoryGuardProvider
     */
    public function testDirectoryIndexExitsBeforeBody($relativePath)
    {
        $path = dirname(dirname(__FILE__)) . '/' . $relativePath;
        $this->assertTrue(is_file($path));
        $real = realpath($path);
        $this->assertInternalType('string', $real);

        $wrapper = tempnam(sys_get_temp_dir(), 'statsreg-guard-');
        $code = '<?php require ' . var_export($real, true) . '; echo "SENTINEL\n";';
        file_put_contents($wrapper, $code);

        $php = PHP_BINARY;
        $cmd = escapeshellarg($php) . ' -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 -d log_errors=0 '
            . escapeshellarg($wrapper);
        $descriptors = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );
        $proc = proc_open($cmd, $descriptors, $pipes);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($proc);
        unlink($wrapper);

        $this->assertSame(0, $exit);
        $this->assertSame('', $stdout);
        $this->assertSame('', $stderr);
        $this->assertNotContains('SENTINEL', $stdout . $stderr);
        $this->assertNoPhpDiagnostics($stdout . $stderr);
    }

    public function directoryGuardProvider()
    {
        return array(
            array('index.php'),
            array('tests/index.php'),
            array('translations/index.php'),
            array('upgrade/index.php'),
        );
    }
}
