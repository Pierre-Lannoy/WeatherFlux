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
	 * Static tags.
	 *
	 * @since   1.0.0
	 * @var     array   $static_tags     Tags to add.
	 */
	private static $static_tags = [];

	/**
	 * Static fields.
	 *
	 * @since   1.0.0
	 * @var     array   $static_fields     Static fields to add.
	 */
	private static $static_fields = [];

	/**
	 * Sensor status.
	 *
	 * @since   1.0.0
	 * @var     array   $sensor_status     Sensor status.
	 */
	private static $sensor_status = [
		'AR' => [
			0b000000001	=> [ 'lightning', 'failed'],
			0b000000010	=> [ 'lightning', 'noise'],
			0b000000100	=> [ 'lightning', 'disturber'],
			0b000001000	=> [ 'pressure', 'failed'],
			0b000010000	=> [ 'temperature', 'failed'],
			0b000100000	=> [ 'r-humidity', 'failed'],
		],
		'SK' => [
			0b001000000	=> [ 'wind', 'failed'],
			0b010000000	=> [ 'precipitation', 'failed'],
			0b100000000	=> [ 'light', 'failed'],
		],
		'ST' => [
			0b000000001	=> [ 'lightning', 'failed'],
			0b000000010	=> [ 'lightning', 'noise'],
			0b000000100	=> [ 'lightning', 'disturber'],
			0b000001000	=> [ 'pressure', 'failed'],
			0b000010000	=> [ 'temperature', 'failed'],
			0b000100000	=> [ 'r-humidity', 'failed'],
			0b001000000	=> [ 'wind', 'failed'],
			0b010000000	=> [ 'precipitation', 'failed'],
			0b100000000	=> [ 'light', 'failed'],
		],
	];

	/**
	 * Reset flags.
	 *
	 * @since   1.0.0
	 * @var     array   $reset_flags     Reset flags.
	 */
	private static $reset_flags = [
		'BOR' => 'brownout_reset',
		'PIN' => 'pin_reset',
		'POR' => 'power_reset',
		'SFT' => 'software_reset',
		'WDG' => 'watchdog_reset',
		'WWD' => 'window-watchdog_reset',
		'LPW' => 'low-power_reset',
	];

	/**
	 * Dynamic fields.
	 *
	 * @since   1.0.0
	 * @var     array   $fields     Dynamic fields to process.
	 */
	private static $fields = [
		'evt_precip' => [ 'ts' ],
		'evt_strike' => [ 'ts', 'strike_distance', 'strike_energy' ],
		'rapid_wind' => [ 'ts', 'wind_speed', 'wind_direction', 'wind_sample_interval' ],
		'obs_air' => [ 'ts', 'pressure_station', 'temperature_air', 'r-humidity', 'strike_count', 'strike_distance', 'battery', 'report_interval' ],
		'obs_sky' => [ 'ts', 'illuminance_sun', 'uv', 'rain_accumulation', 'wind_lull', 'wind_average', 'wind_gust', 'wind_direction', 'battery', 'report_interval', 'irradiance_sun', 'rain_accumulation_local_day', 'precipitation_type', 'wind_sample_interval' ],
		'obs_st' => [ 'ts', 'wind_lull', 'wind_average', 'wind_gust', 'wind_direction', 'wind_sample_interval', 'pressure_station', 'temperature_air', 'r-humidity', 'illuminance_sun', 'uv', 'irradiance_sun', 'rain_accumulation', 'precipitation_type', 'strike_distance', 'strike_count', 'battery', 'report_interval'],
		'device_status' => [ 'uptime', 'voltage', 'firmware_revision', 'rssi_self', 'rssi_hub' ],
		'hub_status' => [ 'uptime', 'firmware_revision', 'rssi_self' ],
	];

	/**
	 * Dynamics fields to forget.
	 *
	 * @since   1.0.0
	 * @var     array   $forgot_fields     Fields to forget.
	 */
	private static $forgot_fields = [ 'ts', 'battery', 'precipitation_type', 'rain_accumulation_local_day' ];

	/**
	 * Observation mode.
	 *
	 * @since   1.0.0
	 * @var     boolean $observation    Pass-through filters for events.
	 */
	private static $observation = false;

	/**
	 * Do the units have to be chosen only in base ISU?
	 *
	 * @since   1.0.0
	 * @var     boolean $strict_isu    Strict ISU conversion.
	 */
	private static $strict_isu = false;

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
	 * Normalizes extracted values.
	 *
	 * @param   array   $data   The extracted values.
	 * @return  array   The normalized extracted values.
	 * @since   1.0.0
	 */
	private function normalize( $data ) {
		if ( array_key_exists( 'strike_distance', $data ) ) {  // convert to meters (m)
			$data['strike_distance'] = 1000 * $data['strike_distance'];
		}
		if ( array_key_exists( 'pressure_station', $data ) ) {  // convert to pascal (Pa)
			$data['pressure_station'] = 100 * $data['pressure_station'];
		}
		if ( array_key_exists( 'report_interval', $data ) ) {  // convert to second (s)
			$data['report_interval'] = 60 * $data['report_interval'];
		}
		if ( array_key_exists( 'rain_accumulation', $data ) ) {  // convert to meters (m)
			$data['rain_accumulation'] = $data['rain_accumulation'] / 1000;
		}
		if ( self::$strict_isu ) {
			if ( array_key_exists( 'temperature_air', $data ) ) {  // convert to kelvin (K)
				$data['temperature_air'] = 273.15 + $data['temperature_air'];
			}
			if ( array_key_exists( 'wind_direction', $data ) ) {  // convert to radian (rad)
				$data['wind_direction'] = ( pi() / 180 ) * $data['wind_direction'];
			}
		}
		return $data;
	}

	/**
	 * Extracts dynamic tags.
	 *
	 * @param   array   $data   Data to process.
	 * @return  array   The extracted tags.
	 * @since   1.0.0
	 */
	private function get_tags( $data ) {
		$result = [];
		switch ( $data['type'] ) {
			case 'evt_precip':
				$result['event'] = 'precipitation';
				break;
			case 'evt_strike':
				$result['event'] = 'strike';
				break;
			case 'obs_sky':
			case 'obs_st':
				if ( array_key_exists( 'obs', $data ) ) {
					if ( 0 < count( $data['obs'] ) ) {
						if ( is_array( $data['obs'][0] ) ) {
							if ( count( $data['obs'][0] ) === count( self::$fields[ $data['type'] ] ) ) {
								switch ( (int) $data['obs'][0][ 'obs_sky' === $data['type'] ? 12 : 13 ] ) {
									case 1:
										$result['precipitation_type'] = 'rain';
										break;
									case 2:
										$result['precipitation_type'] = 'hail';
										break;
								}
							} else {
								$this->logger->warning( sprintf(  '%s returned an unknown data format for %s message.', $data['serial_number'], $data['type'] ) );
							}
						} else {
							$this->logger->warning( sprintf(  '%s returned an unknown data format for %s message.', $data['serial_number'], $data['type'] ) );
						}
					} else {
						$this->logger->warning( sprintf(  '%s returned an unknown data format for %s message.', $data['serial_number'], $data['type'] ) );
					}
				} else {
					$this->logger->warning( sprintf(  '%s returned an unknown data format for %s message.', $data['serial_number'], $data['type'] ) );
				}
				break;
			case 'device_status':
				if ( array_key_exists( 'hub_sn', $data ) ) {
					$result['hub'] = $data['hub_sn'];
				}
				$id = substr( $data['serial_number'], 0, 2 );
				if ( array_key_exists( $id, self::$sensor_status ) && array_key_exists( 'sensor_status', $data ) ) {
					$sensors = (int) $data['sensor_status'];
					foreach ( self::$sensor_status[ $id ] as $key => $sensor ) {
						$result[ $sensor[0] . '_sensor' ] = ( 1 === $sensors & $key ? $sensor[1] : 'ok' );
					}
				}
				break;
			case 'hub_status':
				if ( array_key_exists( 'reset_flags', $data ) ) {
					$flags = explode( ',', $data['reset_flags'] );
					foreach( self::$reset_flags as $key => $flag ) {
						$result[ $flag ] = ( in_array( $key, $flags ) ? 'yes' : 'no' );
					}
				}
				break;
		}
		return $result;
	}

	/**
	 * Extracts dynamic values.
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
			$d[] = 0;
		}
		if ( array_key_exists( 'obs', $data ) ) {
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
		if ( array_key_exists( $data['type'], self::$fields ) && is_array( self::$fields[ $data['type'] ] ) && count( self::$fields[ $data['type'] ] ) === count( $d ) ) {
			for ( $i = 0; $i < count( $d ); $i++ ) {
				$result[ self::$fields[ $data['type'] ][ $i ] ] = $d[ $i ];
			}
		} else {
			$this->logger->warning( sprintf(  '%s returned an unknown data format for % message.', $data['serial_number'], $data['type'] ) );
		}
		foreach ( self::$forgot_fields as $field ) {
			if ( array_key_exists( $field, $result ) ) {
				unset( $result[ $field ] );
			}
		}
		return $this->normalize( $result );
	}

	/**
	 * Formats a line of measurement, tags and values.
	 *
	 * @param   array   $data   Data to process.
	 * @return  array  The formatted lines.
	 * @since   1.0.0
	 */
	private function special_lines( $data ) {
		$result = [];
		if ( array_key_exists( 'fs', $data ) ) {
			$lines = [];
			for ( $i = 0; $i < count( $data['fs'] ); $i++ ) {
				$lines[ sprintf( 'fs_%d', $i ) ] = $data['fs'][$i];
			}
			$line    = $data['serial_number'] . '_fs';
			$tagline = $this->merge_items( [], self::$static_tags, $data['serial_number'] );
			if ( '' !== $tagline ) {
				$line .= ',' . $tagline;
			}
			$fieldline = $this->merge_items( $lines, [], $data['serial_number'] );
			if ( '' !== $fieldline ) {
				$line .= ' ' . $fieldline;
			}
			$result[] = $line;
		}
		if ( array_key_exists( 'mqtt_stats', $data ) ) {
			$lines = [];
			for ( $i = 0; $i < count( $data['mqtt_stats'] ); $i++ ) {
				$lines[ sprintf( 'mqtt_%d', $i ) ] = $data['mqtt_stats'][$i];
			}
			$line    = $data['serial_number'] . '_mqtt';
			$tagline = $this->merge_items( [], self::$static_tags, $data['serial_number'] );
			if ( '' !== $tagline ) {
				$line .= ',' . $tagline;
			}
			$fieldline = $this->merge_items( $lines, [], $data['serial_number'] );
			if ( '' !== $fieldline ) {
				$line .= ' ' . $fieldline;
			}
			$result[] = $line;
		}
		if ( array_key_exists( 'radio_stats', $data ) ) {
			$lines                  = [];
			$lines['version']       = $data['radio_stats'][0];
			$lines['reboot']        = $data['radio_stats'][1];
			$lines['ic2_bus_error'] = $data['radio_stats'][2];
			$lines['network_id']    = $data['radio_stats'][4];
			$line                   = $data['serial_number'] . '_radio';
			$tags                   = [];
			switch ( (int) $data['radio_stats'][2] ) {
				case 0:
					$tags['status'] = 'off';
					break;
				case 1:
					$tags['status'] = 'on';
					break;
				case 2:
					$tags['status'] = 'active';
					break;
			}
			$tagline                = $this->merge_items( $tags, self::$static_tags, $data['serial_number'] );
			if ( '' !== $tagline ) {
				$line .= ',' . $tagline;
			}
			$fieldline = $this->merge_items( $lines, [], $data['serial_number'] );
			if ( '' !== $fieldline ) {
				$line .= ' ' . $fieldline;
			}
			$result[] = $line;
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
		$tagline = $this->merge_items( $this->get_tags( $data ), self::$static_tags, $data['serial_number'] );
		if ( '' !== $tagline ) {
			$result .= ',' . $tagline;
		}
		$fieldline = $this->merge_items( $this->get_values( $data ), self::$static_fields, $data['serial_number'] );
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
			$result['id']     = strtoupper( $data['serial_number'] );
			$result['is_hub'] = false;
			switch ( substr( $result['id'], 0, 2 ) ) {
				case 'AR':
					$result['type'] = 'Air device';
					break;
				case 'HB':
					$result['type'] = 'Hub';
					$result['is_hub'] = true;
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
				$this->logger->notice( sprintf ( 'New %s detected: %s', $device['type'], $device['id'] ), [ 'code' => 666 ] );
			}
			if ( self::$observation ) {
				return;
			} else {
				if ( in_array( $d['type'], self::$filters ) ) {
					$lines[] = $this->format_line( $d );
					if ( $device['is_hub'] ) {
						$lines = array_merge( $lines, $this->special_lines( $d ) );
					}
					foreach ($lines as $line ) {








						$this->logger->debug( $line );
					}
				} else {
					$this->logger->debug( sprintf( 'Message dropped: event filtered (%s).', $d['type'] ) );
				}
			}
		} else {
			$this->logger->debug( 'Message dropped: not a WeatherFlow event.' );
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
			$this->logger->notice( 'Launching' . WF_NAME . ' v' . WF_VERSION );
			if ( self::$strict_isu ) {
				$this->logger->warning( WF_NAME . ' running in strict-ISU mode.' );
			}
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
			if ( in_array( '-d', $argv ) ) {
				$argv = array_diff( $argv, ['-d'] );
			}
		}
		if ( in_array('-o', $argv, true ) ) {
			$argv[] = '-q';
			if ( in_array( '-d', $argv ) ) {
				$argv = array_diff( $argv, ['-d'] );
			}
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
			self::$static_tags = $options['tags'];
		}
		if ( array_key_exists( 'fields', $options ) ) {
			self::$static_fields = $options['fields'];
		}
		if ( array_key_exists( 'unit-system', $options ) && is_array( $options['unit-system'] ) && in_array( 'strict', $options['unit-system'] ) ) {
			self::$strict_isu = true;
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