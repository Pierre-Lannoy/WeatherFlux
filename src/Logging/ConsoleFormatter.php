<?php declare(strict_types=1);
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

use Monolog\Formatter\FormatterInterface;
use Monolog\Logger;

/**
 * Define the Monolog console formatter.
 *
 * Handles all features of console formatter for Monolog.
 *
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class ConsoleFormatter implements FormatterInterface {

	/**
	 * Has the output to be colored?.
	 *
	 * @since  1.0.0
	 * @var    boolean    $colored    Has the output to be colored?.
	 */
	private $colored;

	/**
	 * This is a static variable and not a constant to serve as an extension point for custom levels
	 *
	 * @var array<int, string> $levels Logging levels with the levels as key
	 */
	private static $colors = [
		Logger::DEBUG     => '37',
		Logger::INFO      => '34',
		Logger::NOTICE    => '36',
		Logger::WARNING   => '33',
		Logger::ERROR     => '31',
		Logger::CRITICAL  => '31;1',
		Logger::ALERT     => '35;1',
		Logger::EMERGENCY => '35;5',
	];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   boolean $colored  Optional. Has the output to be colored?.
	 * @since    1.0.0
	 */
	public function __construct( bool $colored = true ) {
		$this->colored = $colored;
	}

	/**
	 * Formats a log record.
	 *
	 * @param  array $record A record to format.
	 * @return string The formatted record.
	 * @since   1.0.0
	 */
	public function format( array $record ): string {
		$line  = date( 'Y-m-d H:i:s' ) . ' ';
		$level = str_pad ( Logger::getLevelName( $record['level'] ), 10, ' ', STR_PAD_RIGHT );
		if ( array_key_exists( 'context', $record ) && array_key_exists( 'code', $record['context'] ) ) {
			$code = str_pad ( (string) (int) $record['context']['code'], 3, '0', STR_PAD_LEFT );
		} else {
			$code = '---';
		}
		if ( array_key_exists( 'message', $record ) ) {
			$message = $record['message'];
		} else {
			$message = '<no message>';
		}
		if ( defined( '\STDOUT' ) && function_exists('posix_isatty') && posix_isatty( \STDOUT ) ) {
			$line = sprintf( "%s\033[%sm%s\033[0m %s", $line, $this->colored ? self::$colors[ Logger::toMonologLevel( $record['level'] ) ] : '', $level . ' [' . $code . ']', $message );
		} else {
			$line .= ' ' . $level . ' [' . $code . '] ' . $message;
		}
		return $line . PHP_EOL;
	}
	/**
	 * Formats a set of log records.
	 *
	 * @param  array $records A set of records to format.
	 * @return string The formatted set of records.
	 * @since   1.0.0
	 */
	public function formatBatch( array $records ): string {
		$messages = [];
		foreach ( $records as $record ) {
			$messages[] = $this->format( $record );
		}
		return implode( '', $messages );
	}
}
