<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Composer\Autoload\Plugin;


use Composer\Autoload\BuildInterface;

interface ClassmapProviderInterface {

    /**
     * @param BuildInterface $build
     * @return string[]
     *   Class map.
     */
    public function buildClassMap(BuildInterface $build = null);
} 
