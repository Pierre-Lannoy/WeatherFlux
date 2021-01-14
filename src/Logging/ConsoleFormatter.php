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
 * @link      https://github.com/Pierre-Lannoy/WeatherFlux"
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace WeatherFlux\Logging;

use Monolog\Formatter\FormatterInterface;

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
	private $colored = true;

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
		$message = date( 'Y-m-d H:i:s' );
		/*$values              = array();
		$values['timestamp'] = date( 'Y-m-d H:i:s' );
		if ( array_key_exists( 'level', $record ) ) {
			if ( array_key_exists( $record['level'], EventTypes::$level_names ) ) {
				$values['level'] = strtolower( EventTypes::$level_names[ $record['level'] ] );
			}
		}
		if ( array_key_exists( 'channel', $record ) ) {
			$values['channel'] = strtolower( $record['channel'] );
		}
		if ( array_key_exists( 'message', $record ) ) {
			$values['message'] = substr( $record['message'], 0, 65000 );
		}
		// Context formatting.
		if ( array_key_exists( 'context', $record ) ) {
			$context = $record['context'];
			if ( array_key_exists( 'class', $context ) ) {
				if ( in_array( $context['class'], ClassTypes::$classes, true ) ) {
					$values['class'] = strtolower( $context['class'] );
				}
			}
			if ( array_key_exists( 'component', $context ) ) {
				$values['component'] = substr( $context['component'], 0, 26 );
			}
			if ( array_key_exists( 'version', $context ) ) {
				$values['version'] = substr( $context['version'], 0, 13 );
			}
			if ( array_key_exists( 'code', $context ) ) {
				$values['code'] = (int) $context['code'];
			}
		}
		// Extra formatting.
		if ( array_key_exists( 'extra', $record ) ) {
			$extra = $record['extra'];
			if ( array_key_exists( 'siteid', $extra ) ) {
				$values['site_id'] = (int) $extra['siteid'];
			}
			if ( array_key_exists( 'sitename', $extra ) && is_string( $extra['sitename'] ) ) {
				$values['site_name'] = substr( $extra['sitename'], 0, 250 );
			}
			if ( array_key_exists( 'userid', $extra ) && is_numeric( $extra['userid'] ) ) {
				$values['user_id'] = substr( (string) $extra['userid'], 0, 66 );
			}
			if ( array_key_exists( 'username', $extra ) && is_string( $extra['username'] ) ) {
				$values['user_name'] = substr( $extra['username'], 0, 250 );
			}
			if ( array_key_exists( 'ip', $extra ) && is_string( $extra['ip'] ) ) {
				$values['remote_ip'] = substr( $extra['ip'], 0, 66 );
			}
			if ( array_key_exists( 'url', $extra ) && is_string( $extra['url'] ) ) {
				$values['url'] = substr( $extra['url'], 0, 2083 );
			}
			if ( array_key_exists( 'http_method', $extra ) && is_string( $extra['http_method'] ) ) {
				if ( in_array( strtolower( $extra['http_method'] ), Http::$verbs, true ) ) {
					$values['verb'] = strtolower( $extra['http_method'] );
				}
			}
			if ( array_key_exists( 'server', $extra ) && is_string( $extra['server'] ) ) {
				$values['server'] = substr( $extra['server'], 0, 250 );
			}
			if ( array_key_exists( 'referrer', $extra ) && $extra['referrer'] && is_string( $extra['referrer'] ) ) {
				$values['referrer'] = substr( $extra['referrer'], 0, 250 );
			}
			if ( array_key_exists( 'ua', $extra ) && $extra['ua'] && is_string( $extra['ua'] ) ) {
				$values['user_agent'] = substr( $extra['ua'], 0, 1024 );
			}
			if ( array_key_exists( 'file', $extra ) && $extra['file'] && is_string( $extra['file'] ) ) {
				$values['file'] = substr( $extra['file'], 0, 250 );
			}
			if ( array_key_exists( 'line', $extra ) && $extra['line'] ) {
				$values['line'] = (int) $extra['line'];
			}
			if ( array_key_exists( 'class', $extra ) && $extra['class'] && is_string( $extra['class'] ) ) {
				$values['classname'] = substr( $extra['class'], 0, 100 );
			}
			if ( array_key_exists( 'function', $extra ) && $extra['function'] && is_string( $extra['function'] ) ) {
				$values['function'] = substr( $extra['function'], 0, 100 );
			}
			if ( array_key_exists( 'trace', $extra ) && $extra['trace'] ) {
				// phpcs:ignore
				$s = serialize( $extra['trace'] );
				if ( strlen( $s ) < 65000 ) {
					$values['trace'] = $s;
				} else {
					$s          = [];
					$s['error'] = 'This backtrace was not recorded: size exceeds limit.';
					// phpcs:ignore
					$values['trace'] = serialize( $s );
				}
			}
		}
		$message[] = $values;*/
		// phpcs:ignore
		return $message . PHP_EOL;
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
