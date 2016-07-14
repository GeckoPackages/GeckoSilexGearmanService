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
 * @author SpacePossum
 */
class GearmanService extends GearmanClient
{
    fail
    /**
     * @var array
     */
    private $servers;

    /**
     * Work around for retrieving the context when not set result on segmentation fault.
     *
     * @var bool
     */
    private $contextSet = false;

    /**
     * @param array<string, int> $servers
     * @param int|null           $timeout
     * @param int|null           $options
     * @param string|null        $context
     */
    public function __construct(array $servers = [], $timeout = null, $options = null, $context = null)
    {
        $this->servers = [];
        foreach ($servers as $host => $port) {
            if (is_int($host)) {
                $this->addServer($port, 4730);
            } else {
                $this->addServer($host, $port);
            }
        }

        if (null !== $context) {
            $this->setContext($context);
        }

        if (null !== $options) {
            $this->setOptions($options);
        }

        $this->setTimeout(null === $timeout ? GEARMAN_DEFAULT_SOCKET_TIMEOUT : $timeout);
    }

    /**
     * {@inheritdoc}
     */
    public function addServer($host = '127.0.0.1', $port = 4730)
    {
        $this->servers[$host] = $port;

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
            $this->servers[$serverExploded[0]] = count($serverExploded) > 1 ? (int) $serverExploded[1] : 4730;
        }

        return parent::addServers($servers);
    }

    /**
     * @return null|string
     */
    public function context()
    {
        if (!$this->contextSet) {
            // not set context segfaults on default -> https://github.com/hjr3/pecl-gearman/issues/15
            return null;
        }

        // trim() -> https://github.com/hjr3/pecl-gearman/issues/15
        return trim(parent::context());
    }

    /**
     * @return string @see GearmanService::context
     */
    public function getContext()
    {
        return $this->context();
    }

    /**
     * @return int @see GearmanClient::options
     */
    public function getOptions()
    {
        return parent::options();
    }

    /**
     * {@inheritdoc}
     */
    public function setContext($context)
    {
        $this->contextSet = true;

        return parent::setContext(trim($context));
    }

    /**
     * Returns servers added to the client.
     *
     * @return array<string, int>
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
     * @return bool
     */
    public function isContextSet()
    {
        return $this->contextSet;
    }
}
