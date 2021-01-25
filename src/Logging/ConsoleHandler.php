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
 * @link      https://github.com/Pierre-Lannoy/WeatherFlux
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace WeatherFlux\Logging;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Formatter\FormatterInterface;

/**
 * Define the Monolog console handler.
 *
 * Handles all features of console handler for Monolog.
 *
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class ConsoleHandler extends AbstractProcessingHandler {

	/**
	 * The stream to write in.
	 *
	 * @since  1.0.0
	 * @var    resource    $stream    Maintains the stream handler.
	 */
	private $stream;

	/**
	 * Has the output to be colored?.
	 *
	 * @since  1.0.0
	 * @var    boolean    $colored    Has the output to be colored?.
	 */
	private $colored;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   boolean $colored  Optional. Has the output to be colored?.
	 * @param   integer $level    Optional. The minimal level to log.
	 * @param   boolean $bubble   Optional. Has the record to bubble?.
	 * @since    1.0.0
	 */
	public function __construct( bool $colored = true, $level = Logger::DEBUG, bool $bubble = true ) {
		parent::__construct( $level, $bubble );
		$this->colored = $colored;
		$this->stream = fopen( 'php://output', 'w' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getDefaultFormatter(): FormatterInterface {
		return new ConsoleFormatter( $this->colored );
	}

	/**
	 * Write the formatted record to stream.
	 *
	 * @param   array $record    The record to write.
	 * @since    1.0.0
	 */
	protected function write( array $record ): void {
		fwrite( $this->stream, $record['formatted'] );
	}
}