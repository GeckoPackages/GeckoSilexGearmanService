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
 * @author SpacePossum
 */
final class GearmanServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGearmanServiceRegisterNoConfiguration()
    {
        $app = new Application();
        $app->register(new GearmanServiceProvider());

        $this->assertTrue(isset($app['gearman']));
        $this->assertInstanceOf(GearmanService::class, $app['gearman']);

        $servers = $app['gearman']->getServers();
        $this->assertInternalType('array', $servers);
        $this->assertCount(1, $servers);
        $this->assertArrayHasKey('127.0.0.1', $servers);
        $this->assertSame(4730, $servers['127.0.0.1']);

        $this->assertSame(GEARMAN_DEFAULT_SOCKET_TIMEOUT, $app['gearman']->getTimeOut());
        $this->assertSame(0, $app['gearman']->getOptions());
        $this->assertSame(null, $app['gearman']->getContext());
    }

    public function testGearmanServiceRegisterWithConfiguration()
    {
        $app = new Application();
        $app->register(
            new GearmanServiceProvider('gearman.config.test'),
            [
                'gearman.config.test.servers' => ['192.168.0.3' => 321],
                'gearman.config.test.timeout' => GEARMAN_DEFAULT_SOCKET_TIMEOUT + 777,
                'gearman.config.test.options' => GEARMAN_CLIENT_NON_BLOCKING | GEARMAN_CLIENT_FREE_TASKS,
                'gearman.config.test.context' => '_test_',
            ]
        );

        $servers = $app['gearman.config.test']->getServers();
        $this->assertCount(1, $servers);
        $this->assertArrayHasKey('192.168.0.3', $servers);

        $this->assertSame(321, $servers['192.168.0.3']);
        $this->assertSame(GEARMAN_DEFAULT_SOCKET_TIMEOUT + 777, $app['gearman.config.test']->getTimeOut());
        $this->assertSame(GEARMAN_CLIENT_NON_BLOCKING | GEARMAN_CLIENT_FREE_TASKS, $app['gearman.config.test']->getOptions());
        $this->assertSame('_test_', $app['gearman.config.test']->getContext());
    }

    public function testGearmanServiceRegisterWithShortConfiguration()
    {
        $app = new Application();
        $app->register(
            new GearmanServiceProvider('gearman.backup'),
            ['gearman.backup.servers' => ['192.168.0.15', '192.168.0.16' => 123]]
        );

        $this->assertSame(['192.168.0.15' => 4730, '192.168.0.16' => 123], $app['gearman.backup']->getServers());
    }

    public function testGearmanServiceRegisterNaming()
    {
        $app = new Application();
        $app->register(new GearmanServiceProvider('gearman.test'));

        $this->assertFalse(isset($app['gearman']));
        $this->assertTrue(isset($app['gearman.test']));
        $this->assertInstanceOf(GearmanService::class, $app['gearman.test']);
    }

    public function testAddGetServers()
    {
        $app = new Application();
        $app->register(new GearmanServiceProvider());

        for ($i = 0; $i < 2; ++$i) {
            $app['gearman']->addServer('192.168.0.0', 765);
            $servers = $app['gearman']->getServers();

            $this->assertCount(2, $servers);

            $this->assertArrayHasKey('127.0.0.1', $servers);
            $this->assertSame(4730, $servers['127.0.0.1']);

            $this->assertArrayHasKey('192.168.0.0', $servers);
            $this->assertSame(765, $servers['192.168.0.0']);
        }

        $app->register(new GearmanServiceProvider('gearman.2'));
        $app['gearman.2']->addServer('192.168.0.1', 765);

        $servers = $app['gearman']->getServers();

        $this->assertCount(2, $servers);

        $this->assertArrayHasKey('127.0.0.1', $servers);
        $this->assertSame(4730, $servers['127.0.0.1']);

        $this->assertArrayHasKey('192.168.0.0', $servers);
        $this->assertSame(765, $servers['192.168.0.0']);

        $servers = $app['gearman.2']->getServers();

        $this->assertCount(2, $servers);

        $this->assertArrayHasKey('127.0.0.1', $servers);
        $this->assertSame(4730, $servers['127.0.0.1']);

        $this->assertArrayHasKey('192.168.0.1', $servers);
        $this->assertSame(765, $servers['192.168.0.1']);
    }

    public function testAddGetServers2()
    {
        $app = new Application();
        $app->register(new GearmanServiceProvider());
        $app['gearman']->addServers('10.0.0.1,10.0.0.2:7003');

        $this->assertSame(
            [
                '127.0.0.1' => 4730,
                '10.0.0.1' => 4730,
                '10.0.0.2' => 7003,
            ],
            $app['gearman']->getServers()
        );

        $app['gearman']->addServers('127.0.0.5');
        $this->assertSame(
            [
                '127.0.0.1' => 4730,
                '10.0.0.1' => 4730,
                '10.0.0.2' => 7003,
                '127.0.0.5' => 4730,
            ],
            $app['gearman']->getServers()
        );

        $app['gearman']->addServer('192.168.0.4');
        $this->assertSame(
            [
                '127.0.0.1' => 4730,
                '10.0.0.1' => 4730,
                '10.0.0.2' => 7003,
                '127.0.0.5' => 4730,
                '192.168.0.4' => 4730,
            ],
            $app['gearman']->getServers()
        );
    }
}
