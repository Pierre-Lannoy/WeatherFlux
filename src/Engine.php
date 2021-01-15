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
	 * The events filters.
	 *
	 * @since   1.0.0
	 * @var     array   $filters    Pass-through filters for events.
	 */
	private static $filters = [];

	/**
	 * Tags.
	 *
	 * @since   1.0.0
	 * @var     array   $tags     Tags to add.
	 */
	private static $tags = [];

	/**
	 * Static fields.
	 *
	 * @since   1.0.0
	 * @var     array   $fields     Static fields to add.
	 */
	private static $fields = [];

	/**
	 * Observation mode.
	 *
	 * @since   1.0.0
	 * @var     boolean $observation    Pass-through filters for events.
	 */
	private static $observation = false;

	/**
	 * Discovered devices.
	 *
	 * @since   1.0.0
	 * @var     array   $devices    List of already discovered devices.
	 */
	private static $devices = [];

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
	}

	/**
	 * Merges items for a specific device.
	 *
	 * @param   array   $initial      Initial data.
	 * @param   array   $additional   Additional data.
	 * @param   string  $id           Device ID.
	 * @return  string  The merged items, in a line.
	 * @since   1.0.0
	 */
	private function merge_items( $initial, $additional, $id ) {
		$items = $initial;
		if ( array_key_exists( '*', $additional ) ) {
			$items = array_merge( $items, $additional['*'] );
		}
		if ( array_key_exists( $id, $additional ) ) {
			$items = array_merge( $items, $additional[ $id ] );
		}
		$result = [];
		if ( 0 < count( $items ) ) {
			foreach ( $items as $key => $tag ) {
				$result[] = $key . '=' . ( is_string( $tag ) ? str_replace( ' ', '\ ', $tag ) : $tag );
			}
		}
		return implode( ',', $result );
	}

	/**
	 * Extracts values.
	 *
	 * @param   array   $data   Data to process.
	 * @return  array   The extracted values.
	 * @since   1.0.0
	 */
	private function get_values( $data ) {
		$result = [];
		$d      = [];
		if ( array_key_exists( 'evt', $data ) ) {
			$d = $data['evt'];
		}
		if ( array_key_exists( 'ob', $data ) ) {
			$d = $data['ob'];
		}
		if ( array_key_exists( 's', $data ) ) {
			if ( 0 < count( $data['obs'] ) ) {
				$d = $data['obs'][0];
			}
		}
		if ( 0 === count( $d ) ) {
			if ( array_key_exists( 'uptime', $data ) ) {
				$d[] = $data['uptime'];
			}
			if ( array_key_exists( 'voltage', $data ) ) {
				$d[] = $data['voltage'];
			}
			if ( array_key_exists( 'firmware_revision', $data ) ) {
				$d[] = $data['firmware_revision'];
			}
			if ( array_key_exists( 'rssi', $data ) ) {
				$d[] = $data['rssi'];
			}
			if ( array_key_exists( 'hub_rssi', $data ) ) {
				$d[] = $data['hub_rssi'];
			}
		}
		switch ( $data['type'] ) {
			case 'evt_precip':
			case 'evt_strike':
				$suffix = 'event';
				break;
			case 'rapid_wind':
			case 'obs_air':
			case 'obs_sky':
			case 'obs_st':
				$suffix = 'observation';
				break;
			case 'device_status':
			case 'hub_status':
				$suffix = 'status';
				break;
		}

		return $result;
	}

	/**
	 * Formats a line of measurement, tags and values.
	 *
	 * @param   array   $data   Data to process.
	 * @return  string  The formatted line.
	 * @since   1.0.0
	 */
	private function format_line( $data ) {
		switch ( $data['type'] ) {
			case 'evt_precip':
			case 'evt_strike':
				$suffix = 'event';
				break;
			case 'rapid_wind':
			case 'obs_air':
			case 'obs_sky':
			case 'obs_st':
				$suffix = 'observation';
				break;
			case 'device_status':
			case 'hub_status':
				$suffix = 'status';
				break;
		}
		$result  = $data['serial_number'] . '_' . $suffix;
		$tagline = $this->merge_items( [], self::$tags, $data['serial_number'] );
		if ( '' !== $tagline ) {
			$result .= ',' . $tagline;
		}
		$fieldline = $this->merge_items( [], self::$fields, $data['serial_number'] );
		if ( '' !== $fieldline ) {
			$result .= ' ' . $fieldline;
		}
		return $result;
	}

	/**
	 * Extracts devices generic details.
	 *
	 * @param   array   $data   Data to process.
	 * @return  array   The generic details of the device.
	 * @since   1.0.0
	 */
	private function get_device( $data ) {
		$result = [
			'type'   => 'unknown',
			'id'     => '',
			'uptime' => 0,
			'rev'    => 0,
		];
		if ( array_key_exists( 'serial_number', $data ) ) {
			$result['id'] = strtoupper( $data['serial_number'] );
			switch ( substr( $result['id'], 0, 2 ) ) {
				case 'AR':
					$result['type'] = 'Air device';
					break;
				case 'HB':
					$result['type'] = 'Hub';
					break;
				case 'SK':
					$result['type'] = 'Sky device';
					break;
				case 'ST':
					$result['type'] = 'Tempest device';
					break;
			}
		}
		if ( array_key_exists( 'uptime', $data ) ) {
			$result['uptime'] = (int) $data['uptime'];
		}
		if ( array_key_exists( 'firmware_revision', $data ) ) {
			$result['rev'] = (int) $data['firmware_revision'];
		}
		return $result;
	}

	/**
	 * Process the received data.
	 *
	 * @param   string  $data   Data to process.
	 * @since   1.0.0
	 */
	private function process( $data ) {
		try {
			$d = @\json_decode( $data, true );
			if ( \json_last_error() !== JSON_ERROR_NONE ) {
				$this->logger->debug( 'Message dropped: not a valid JSON element.' );
				return;
			}
		} catch ( \Throwable $e ) {
			$this->logger->debug( 'Message dropped: ' . $e->getMessage(), [ 'code' => $e->getCode() ] );
			return;
		}
		if ( array_key_exists( 'type', $d ) && array_key_exists( 'serial_number', $d ) ) {
			$device = $this->get_device( $d );
			if ( ! in_array( $device['id'], self::$devices ) ) {
				self::$devices[] = $device['id'];
				if ( self::$observation ) {
					$this->logger->notice( sprintf ( 'New %s detected: %s', $device['type'], $device['id'] ), [ 'code' => 666 ] );
				}
			}
			if ( self::$observation ) {
				return;
			} else {
				if ( in_array( $d['type'], self::$filters ) ) {
					$line = $this->format_line( $d );



					$this->logger->debug( $line );



				} else {
					$this->logger->debug( sprintf( 'Message dropped: event filtered (%s).', $d['type'] ) );
				}
			}

		} else {
			$this->logger->debug( 'Message dropped: not WeatherFlow event.' );
			return;
		}
	}

	/**
	 * Start the engine.
	 *
	 * @since   1.0.0
	 */
	private function start() {
		try {
			$ws_worker = new Worker( 'udp://0.0.0.0:50222' );
			$ws_worker->count = 1;
		} catch ( \Throwable $e ) {
			$this->logger->emergency( 'Unable to create worker: ' . $e->getMessage(), [ 'code' => $e->getCode() ] );
		}
		$ws_worker->onWorkerStart = function( $worker ) {
			$this->logger->notice( 'Worker launched.' );
			$this->logger->debug( 'Started listening on UDP port 50222.' );
		};
		$ws_worker->onWorkerStop = function( $worker ) {
			$this->logger->debug( 'Stopped listening on UDP port 50222.' );
			$this->logger->notice( 'Worker stopped.' );
		};
		$ws_worker->onError = function( $connection, $code, $msg ) {
			$this->logger->error( $msg, [ 'code' => $code ]);
		};
		$ws_worker->onMessage = function ( $connection, $data ) {
			$this->process( $data );
		};
		try {
			Worker::runAll();
		} catch ( \Throwable $e ) {
			$this->logger->alert( $e->getMessage(), [ 'code' => $e->getCode() ] );
		}
	}

	/**
	 * Command line arguments processing.
	 *
	 * @since   1.0.0
	 */
	private static function arguments() {
		global $argv;
		if ( in_array('-c', $argv, true ) ) {
			$argv[] = '-q';
		}
		if ( in_array('-o', $argv, true ) ) {
			$argv[] = '-q';
			self::$observation = true;
		}
	}

	/**
	 * Options processing.
	 *
	 * @param   array   $options    Optional. Operations options.
	 * @since   1.0.0
	 */
	private static function options( $options ) {
		if ( ! is_array( $options ) ) {
			return;
		}
		if ( array_key_exists( 'filters', $options ) ) {
			self::$filters = $options['filters'];
		}
		if ( array_key_exists( 'tags', $options ) ) {
			self::$tags = $options['tags'];
		}
		if ( array_key_exists( 'fields', $options ) ) {
			self::$fields = $options['fields'];
		}
	}

	/**
	 * Create an instance if needed, then run it.
	 *
	 * @param   array   $options    Optional. Operations options.
	 * @since   1.0.0
	 */
	public static function run( $options = [] ) {
		if ( ! isset( self::$engine ) ) {
			self::arguments();
			self::options( $options );
			self::$engine = new Engine();
			self::$engine->start();
		}
	}
}