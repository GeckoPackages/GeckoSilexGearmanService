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
            return new GearmanService(
                isset($app[$name.'.servers']) ? $app[$name.'.servers'] : ['127.0.0.1' => 4730],
                isset($app[$name.'.timeout']) ? $app[$name.'.timeout'] : null,
                isset($app[$name.'.options']) ? $app[$name.'.options'] : null,
                isset($app[$name.'.context']) ? $app[$name.'.context'] : null
            );
        };
    }
}
