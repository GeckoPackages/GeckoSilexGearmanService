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

use GearmanClient;

/**
 * @final
 * @internal
 *
 * @author SpacePossum
 */
final class GearmanService extends GearmanClient
{
    /**
     * @var array
     */
    private $servers = [];

    /**
     * {@inheritdoc}
     */
    public function addServer($host = '127.0.0.1', $port = 4730)
    {
        $this->putServer($host, $port);

        return parent::addServer($host, $port);
    }

    /**
     * {@inheritdoc}
     */
    public function addServers($servers = '127.0.0.1:4730')
    {
        $serversExploded = explode(',', $servers);
        foreach ($serversExploded as $serverExploded) {
            $serverExploded = explode(':', $serverExploded);
            $this->putServer($serverExploded[0], count($serverExploded) > 1 ? $serverExploded[1] : 4730);
        }

        return parent::addServers($servers);
    }

    /**
     * @return int @see GearmanClient::options
     */
    public function getOptions()
    {
        return parent::options();
    }

    /**
     * Returns the servers added to the client.
     *
     * Note:
     * Do not rely on the format of the keys as the format is not guaranteed.
     * Well unlikely the format of the keys may change in the future these
     * are still considered arbitrary.
     *
     * @return array<string, array<string, int>>
     */
    public function getServers()
    {
        return $this->servers;
    }

    /**
     * @return int @see GearmanClient::timeout
     */
    public function getTimeOut()
    {
        return parent::timeout();
    }

    /**
     * @param string     $host
     * @param string|int $port
     */
    private function putServer($host, $port)
    {
        $this->servers[$host.':'.$port] = [(string) $host => (int) $port];
    }
}
