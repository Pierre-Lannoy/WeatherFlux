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

use Workerman\Worker;
use WeatherFlux\Logging\ConsoleHandler;
use \Monolog\Logger;

class Engine {

	/**
	 * The engine instance.
	 *
	 * @since   1.0.0
	 * @var     Engine  $engine     Maintains the engine instance.
	 */
	private static $engine = null;

	/**
	 * The logger instance.
	 *
	 * @since   1.0.0
	 * @var     \Monolog\Logger  $logger     Maintains the logger instance.
	 */
	private $logger = null;

	/**
	 * Initializes the instance and set its properties.
	 *
	 * @since   1.0.0
	 */
	private function __construct() {
		$this->logger = new Logger('console');
		$this->logger->pushHandler( new ConsoleHandler() );
		$this->logger->debug( 'Logger started.');
	}

	/**
	 * Start the engine.
	 *
	 * @since   1.0.0
	 */
	private function start() {

	}

	/**
	 * Create an instance if needed, then run it.
	 *
	 * @since   1.0.0
	 */
	public static function run() {
		if ( ! isset( self::$engine ) ) {
			self::$engine = new Engine();
			self::$engine->start();
		}
	}
}