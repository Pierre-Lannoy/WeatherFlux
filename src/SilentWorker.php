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

namespace WeatherFlux;

/**
 * Makes Workerman\Worker silent.
 */
class SilentWorker extends \Workerman\Worker {

	/**
	 * Does not log.
	 *
	 * @param string $msg The message to log.
	 * @since   2.0.0
	 */
	public static function log( $msg ) {

	}

}