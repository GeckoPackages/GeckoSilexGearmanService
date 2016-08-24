<?php

/*
 * This file is part of the GeckoPackages.
 *
 * (c) GeckoPackages https://github.com/GeckoPackages
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace GeckoPackages\Silex\Services\Gearman;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * @author SpacePossum
 */
final class GearmanServiceProvider implements ServiceProviderInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name = 'gearman')
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        $name = $this->name;
        $app[$name] = function ($app) use ($name) {
            $client = new GearmanService();

            if (isset($app[$name.'.servers'])) {
                foreach ($app[$name.'.servers'] as $host => $port) {
                    // if the host is an int it is the index of an array and the value of it is the host
                    if (is_int($host)) {
                        $client->addServer($port, 4730);
                    } else {
                        $client->addServer($host, $port);
                    }
                }
            } else {
                $client->addServer('127.0.0.1', 4730);
            }

            if (isset($app[$name.'.options'])) {
                $client->setOptions($app[$name.'.options']);
            }

            $client->setTimeout(isset($app[$name.'.timeout']) ? $app[$name.'.timeout'] : GEARMAN_DEFAULT_SOCKET_TIMEOUT);

            return $client;
        };
    }
}
