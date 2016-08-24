<?php

/*
 * This file is part of the GeckoPackages.
 *
 * (c) GeckoPackages https://github.com/GeckoPackages
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use GeckoPackages\Silex\Services\Gearman\GearmanService;
use GeckoPackages\Silex\Services\Gearman\GearmanServiceProvider;
use Silex\Application;

/**
 * @internal
 *
 * @requires extension gearman
 *
 * @author SpacePossum
 */
final class GearmanServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array<string, int>
     */
    private $defaultServer;
    private $defaultHost = '127.0.0.1';
    private $defaultPort = 4730;

    /**
     * @var array<string, int>
     */
    private $testServer;
    private $testServerHost = '127.0.0.1';
    private $testServerPort = 123;

    protected function setUp()
    {
        $this->defaultServer = [$this->defaultHost => $this->defaultPort];
        $this->testServer = [$this->testServerHost => $this->testServerPort];
    }

    public function testGearmanServiceRegisterNoConfiguration()
    {
        $defaultClient = new \GearmanClient();
        $app = new Application();
        $app->register(new GearmanServiceProvider());

        $this->assertTrue(isset($app['gearman']));
        $this->assertInstanceOf(GearmanService::class, $app['gearman']);
        $this->assertServers([$this->defaultServer], $app['gearman']->getServers());
        $this->assertSame(GEARMAN_DEFAULT_SOCKET_TIMEOUT, $app['gearman']->getTimeOut(), 'Expected default socket timeout.');
        $this->assertSame($defaultClient->options(), $app['gearman']->getOptions(), 'Expected default options as none should be set.');
    }

    public function testGearmanServiceRegisterWithConfiguration()
    {
        $app = new Application();
        $app->register(
            new GearmanServiceProvider('gearman.config.test'),
            [
                'gearman.config.test.servers' => $this->testServer,
                'gearman.config.test.timeout' => GEARMAN_DEFAULT_SOCKET_TIMEOUT + 777,
                'gearman.config.test.options' => GEARMAN_CLIENT_NON_BLOCKING | GEARMAN_CLIENT_FREE_TASKS,
            ]
        );

        $this->assertServers([$this->testServer], $app['gearman.config.test']->getServers());
        $this->assertSame(GEARMAN_DEFAULT_SOCKET_TIMEOUT + 777, $app['gearman.config.test']->getTimeOut(), 'Expected timeout set mismatched.');
        $this->assertSame(GEARMAN_CLIENT_NON_BLOCKING | GEARMAN_CLIENT_FREE_TASKS, $app['gearman.config.test']->getOptions());
    }

    public function testGearmanServiceRegisterWithShortConfiguration()
    {
        $app = new Application();
        $app->register(
            new GearmanServiceProvider('gearman.backup'),
            [
                'gearman.backup.servers' => [key($this->testServer)] + $this->testServer,
                'gearman.backup.timeout' => 0,
                'gearman.backup.options' => 0,
            ]
        );

        $this->assertServers([$this->defaultServer, $this->testServer], $app['gearman.backup']->getServers());
        $this->assertSame(0, $app['gearman.backup']->getTimeOut(), 'Expected timeout set mismatched.');
        $this->assertSame(0, $app['gearman.backup']->getOptions());
    }

    public function testGearmanServiceRegisterNaming()
    {
        $app = new Application();
        $app->register(new GearmanServiceProvider('gearman.test'));

        $this->assertFalse(isset($app['gearman']));
        $this->assertTrue(isset($app['gearman.test']));
        $this->assertInstanceOf(GearmanService::class, $app['gearman.test']);

        $app->register(new GearmanServiceProvider('gearman'));
        $this->assertTrue(isset($app['gearman']));

        $this->assertNotSame($app['gearman'], $app['gearman.test']);
    }

    public function testAddGetServer()
    {
        $app = new Application();
        $app->register(new GearmanServiceProvider());

        $expectedServers = [$this->defaultServer, $this->testServer];
        // test no double servers set (and/or in return value).
        for ($i = 0; $i < 3; ++$i) {
            $app['gearman']->addServer($this->testServerHost, $this->testServerPort);
            $this->assertServers($expectedServers, $app['gearman']->getServers());
        }

        $this->assertServers($expectedServers, $app['gearman']->getServers());
    }

    public function testAddGetServers()
    {
        $app = new Application();
        $app->register(new GearmanServiceProvider());

        $expectedServers = [$this->defaultServer, $this->testServer];
        // test no double servers set (and/or in return value).
        for ($i = 0; $i < 3; ++$i) {
            $app['gearman']->addServers($this->testServerHost.':'.$this->testServerPort);
            $this->assertServers($expectedServers, $app['gearman']->getServers());
        }

        $this->assertServers($expectedServers, $app['gearman']->getServers());
    }

    private function assertServers(array $expected, $actual)
    {
        $this->assertInternalType('array', $actual, 'Servers must be of type array.');

        if (count($expected) !== count($actual)) {
            $msg = sprintf(
                "Expected number of servers \"%d\" mismatched actual \"%d\".\n---------\nExpected:\n---------\n%s\n---------\nActual:\n---------\n%s\n---------\n",
                count($expected), count($actual), var_export($expected, true), var_export($actual, true)
            );

            $this->fail($msg);
        }

        // test format and types of all actual servers

        /** @var array $actual */
        foreach ($actual as $key => $actualServer) {
            $this->assertInternalType('array', $actualServer, sprintf('Server "%s" must be an array.', $key));
            $this->assertCount(1, $actualServer, sprintf('Server "%s" must be an array with one element.', $key));

            $host = key($actualServer);

            $this->assertInternalType('string', $host, 'Expected the key to be string as it should be the host.');
            $this->assertInternalType('int', $actualServer[$host], 'Expected the value to be an int as it should be the port.');
        }

        // test format and types of all expected servers and
        // test actual servers against the expected servers
        $notFound = [];

        foreach ($expected as $key => $expectedServer) {
            $this->assertInternalType('array', $expectedServer, sprintf('Server "%s" must be an array.', $key));
            $this->assertCount(1, $expectedServer, sprintf('Server "%s" must be an array with one element.', $key));

            $expectedHost = key($expectedServer);
            $expectedPort = $expectedServer[$expectedHost];

            $this->assertInternalType('string', $expectedHost, 'Expected host must be a string, test is invalid.');
            $this->assertInternalType('int', $expectedPort, 'Expected port must be an int, test is invalid.');

            $found = false;
            foreach ($actual as $actualKey => $actualServer) {
                $actualHost = key($actualServer);
                $actualPort = $actualServer[$actualHost];
                if ($actualHost === $expectedHost && $actualPort === $expectedPort) {
                    $found = true;

                    break;
                }
            }

            if (false === $found) {
                $notFound[$expectedHost] = $expectedPort;
            }
        }

        if (count($notFound)) {
            $failMessage = 'The following expected servers are not found:';
            foreach ($notFound as $host => $port) {
                $failMessage .= sprintf("\n- %20s:%d", $host, $port);
            }

            $failMessage .= "\nIn expected:";
            foreach ($expected as $expectedHost => $expectedPort) {
                $failMessage .= sprintf("\n- %20s:%d", $expectedHost, $expectedPort);
            }

            $this->fail($failMessage);
        }
    }
}
