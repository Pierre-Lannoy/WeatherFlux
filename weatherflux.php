<?php
/**
 * This file is part of WeatherFlux.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    Pierre Lannoy <https://pierre.lannoy.fr/>
 * @copyright Pierre Lannoy <https://pierre.lannoy.fr/>
 * @link      https://github.com/Pierre-Lannoy/WeatherFlux"
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use WeatherFlux\Engine;
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/autoload.php';
require_once __DIR__ . '/options.php';

define( 'WF_NAME', 'WeatherFlux' );
define( 'WF_VERSION', '1.0.0-dev' );

Engine::run( $options );