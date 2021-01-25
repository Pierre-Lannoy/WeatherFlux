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

namespace WeatherFlux;

use ObservableWorker\Worker;
use ObservableWorker\Timer;
use Khill\Duration\Duration;
use WeatherFlux\Logging\ConsoleHandler;
use WeatherFlux\Logging\DockerConsoleHandler;
use InfluxDB2\Client as InfluxClient;
use InfluxDB2\Model\WritePrecision as InfluxWritePrecision;
use \Monolog\Logger;

/**
 * The main WeatherFlux engine class.
 */
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
	private static $logger = null;

	/**
	 * Is it dockerized?
	 *
	 * @since   2.0.0
	 * @var     boolean $starting    Is it dockerized?
	 */
	private static $docker = false;

	/**
	 * Configuration filename.
	 *
	 * @since   2.0.0
	 * @var     string   $devices    Maintains the configuration filename.
	 */
	private static $config = '';

	/**
	 * Discovered devices.
	 *
	 * @since   1.0.0
	 * @var     array   $devices    List of already discovered devices.
	 */
	private static $devices = [];

	/**
	 * Configuration reloading interval.
	 *
	 * @since   2.0.0
	 * @var     integer   $conf_reload  Configuration reloading interval in seconds. Overridden by WF_CONF_RELOAD env var.
	 */
	private static $conf_reload = 120;

	/**
	 * Statistics publishing interval.
	 *
	 * @since   2.0.0
	 * @var     integer   $statistics   Statistics publishing interval in seconds. Overridden by WF_STAT_PUBLISH env var.
	 */
	private static $statistics = 600;

	/**
	 * Running mode.
	 *
	 * @since   2.0.0
	 * @var     string   $running_mode    Maintains the running mode.
	 */
	private static $running_mode = 'unknown';

	/**
	 * Is it starting?
	 *
	 * @since   2.0.0
	 * @var     boolean $starting    Is it starting?
	 */
	private $starting = true;

	/**
	 * Current statistics.
	 *
	 * @since   2.0.0
	 * @var     array   $stat    Current statistics.
	 */
	private $stat = [
		'processed' => 0,
		'dropped'   => 0,
		'sent'      => 0,
		'unsent'    => 0,
	];

	/**
	 * Start timestamp.
	 *
	 * @since   2.0.0
	 * @var     integer   $statistics   Start timestamp.
	 */
	private $start_time = 0;

	/**
	 * The events filters.
	 *
	 * @since   1.0.0
	 * @var     array   $filters    Pass-through filters for events.
	 */
	private $filters = [];

	/**
	 * Static tags.
	 *
	 * @since   1.0.0
	 * @var     array   $static_tags     Tags to add.
	 */
	private $static_tags = [];

	/**
	 * Static fields.
	 *
	 * @since   1.0.0
	 * @var     array   $static_fields     Static fields to add.
	 */
	private $static_fields = [];

	/**
	 * Connexion options.
	 *
	 * @since   1.0.0
	 * @var     array   $influx_connection     The InfluxDB2 connexion options.
	 */
	private $influx_connection = [];

	/**
	 * Sensor status.
	 *
	 * @since   1.0.0
	 * @var     array   $sensor_status     Sensor status.
	 */
	private $sensor_status = [
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
	private $reset_flags = [
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
	private $fields = [
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
	private $forgot_fields = [ 'ts', 'battery', 'precipitation_type', 'rain_accumulation_local_day' ];

	/**
	 * Do the units have to be chosen only in base ISU?
	 *
	 * @since   1.0.0
	 * @var     boolean $strict_isu    Strict ISU conversion.
	 */
	private $strict_isu = false;

	/**
	 * Do the host name have to be dropped?
	 *
	 * @since   2.0.0
	 * @var     boolean $host_drop    Do the host name have to be dropped?
	 */
	private $host_drop = false;

	/**
	 * Force the host name.
	 *
	 * @since   2.0.0
	 * @var     string $host_drop    Name of the host to enforce
	 */
	private $host_override = '';

	/**
	 * The InfluxDB2 client.
	 *
	 * @since   1.0.0
	 * @var     \InfluxDB2\Client  $influx     Maintains the InfluxDB2 client.
	 */
	private $influx = null;

	/**
	 * Initializes the instance and set its properties.
	 *
	 * @since   1.0.0
	 */
	private function __construct() {
		$this->get_options();
		$this->starting = false;
	}

	/**
	 * Options processing.
	 *
	 * @return  boolean     True if options was found and loaded, false otherwise.
	 * @since   2.0.0
	 */
	private function get_options() {
		$options = [];
		if ( file_exists( self::$config ) ) {
			try {
				$options = json_decode( file_get_contents( self::$config, true ), true );
			} catch ( \Throwable $e ) {
				if ( $this->starting ) {
					self::$logger->alert( sprintf( 'Unable to read configuration: %s.', $e->getMessage() ), [ 'code' => $e->getCode() ] );
					$this->abort( 3 );
				} else {
					self::$logger->critical( sprintf( 'Unable to read configuration: %s. Keeping current configuration.', $e->getMessage() ), [ 'code' => $e->getCode() ] );
					return false;
				}
			}
		} else {
			if ( $this->starting ) {
				self::$logger->alert( 'Configuration file not found.', [ 'code' => 404 ] );
				$this->abort( 4 );
			} else {
				self::$logger->critical( 'Configuration file not found. Keeping current configuration.', [ 'code' => 404 ] );
				return false;
			}
		}
		if ( ! is_array( $options ) || 0 === count( $options ) ) {
			if ( $this->starting ) {
				self::$logger->alert( 'Configuration file is unreadable, corrupted or empty.', [ 'code' => 204 ] );
				$this->abort( 5 );
			} else {
				self::$logger->critical( 'Configuration file is unreadable, corrupted or empty. Keeping current configuration.', [ 'code' => 204 ] );
				return false;
			}
		}
		if ( array_key_exists( 'filters', $options ) ) {
			$this->filters = $options['filters'];
		}
		if ( array_key_exists( 'tags', $options ) ) {
			$this->static_tags = $options['tags'];
		}
		if ( array_key_exists( 'fields', $options ) ) {
			$this->static_fields = $options['fields'];
		}

		// Sets ISU mode
		$old = $this->strict_isu;
		if ( array_key_exists( 'isu-mode', $options ) && 'strict' === $options['isu-mode'] ) {
			$this->strict_isu = true;
		}
		if ( $old !== $this->strict_isu && ! $this->starting ) {
			if ( $this->strict_isu ) {
				self::$logger->warning( sprintf ( 'ISU mode changed from "%s" to "%s".', 'derived', 'strict' ) );
			} else {
				self::$logger->warning( sprintf ( 'ISU mode changed from "%s" to "%s".', 'strict', 'derived' ) );
			}
		}
		// Sets host
		if ( array_key_exists( 'host', $options ) && is_array( $options['host'] ) ) {
			if ( array_key_exists( 'override', $options['host'] ) && '' !== (string) $options['host']['override'] ) {
				$this->static_tags['*']['host'] = $this->clean_tag( $options['host']['override'] );
			} else {
				$this->static_tags['*']['host'] = $this->clean_tag( (string) \gethostname() );
			}
			if ( array_key_exists( 'drop', $options['host'] ) && (bool) $options['host']['drop'] ) {
				unset( $this->static_tags['*']['host'] );
			}
		}
		// Sets InfluxDB connection
		if ( 'observation' !== self::$running_mode ) {
			$old = $this->influx_connection;
			$ok  = false;
			if ( array_key_exists( 'influxb', $options ) ) {
				$this->influx_connection = $options['influxb'];
				if ( $old !== $this->influx_connection ) {
					if ( array_key_exists( 'url', $this->influx_connection ) && array_key_exists( 'org', $this->influx_connection ) && array_key_exists( 'token', $this->influx_connection ) && array_key_exists( 'bucket', $this->influx_connection ) ) {
						try {
							self::$logger->info( 'New InfluxDB connection settings.' );
							if ( isset( $this->influx ) && ! $this->influx->closed ) {
								$this->influx->close();
								$this->influx = null;
							}
							$client       = new InfluxClient( array_merge( $this->influx_connection, [ 'precision' => InfluxWritePrecision::MS, 'logFile' => '/dev/null' ] ) );
							$health       = $client->health();
							if ( 'pass' === $health->getStatus() ) {
								$this->influx = $client->createWriteApi();
								$ok           = true;
								self::$logger->info( sprintf( 'Connected to InfluxDB v%s.', $health->getVersion() ) );
							} else {
								$message = preg_replace('/\[.*: /miU', '', $health->getMessage() );
								$message = str_replace( '(see https://curl.haxx.se/libcurl/c/libcurl-errors.html) ', '', $message );
								self::$logger->error( sprintf( 'Unable to connect to InfluxDB: %s.', $message ) );
							}
						} catch ( \Throwable $e ) {
							$this->influx = null;
							self::$logger->error( sprintf( 'Unable to connect to InfluxDB: %s.', $e->getMessage() ), [ 'code' => $e->getCode() ] );
						}
					}
				} else {
					$ok = true;
				}
			}
			if ( ! $ok ) {
				self::$logger->error( 'InfluxDB connection settings are incorrect, nothing will be send.', [ 'code' => 503 ] );
			}
		}
		return true;
	}

	/**
	 * Clean a tag.
	 *
	 * @param string    $tag    The tag to clean.
	 * @return string   The cleaned tag.
	 * @since   2.0.0
	 */
	private function clean_tag( $tag ) {
		$result = str_replace( [ ',', ';' ], '', $tag );
		$result = str_replace( ' ', '\ ', $result );
		return $result;
	}

	/**
	 * Initializes the instance and set its properties.
	 *
	 * @since   1.0.0
	 */
	private function abort( $code ) {
		self::$logger->emergency( 'Stopping immediately.' );
		exit( $code );
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
		if ( array_key_exists( substr( $id, 0, 2 ) . '*', $additional ) ) {
			$items = array_merge( $items, $additional[ substr( $id, 0, 2 ) . '*' ] );
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
		if ( $this->strict_isu ) {
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
							if ( count( $data['obs'][0] ) === count( $this->fields[ $data['type'] ] ) ) {
								switch ( (int) $data['obs'][0][ 'obs_sky' === $data['type'] ? 12 : 13 ] ) {
									case 1:
										$result['precipitation_type'] = 'rain';
										break;
									case 2:
										$result['precipitation_type'] = 'hail';
										break;
									default:
										$result['precipitation_type'] = 'none';
								}
							} else {
								self::$logger->warning( sprintf(  '%s returned an unknown data format for %s message.', $data['serial_number'], $data['type'] ) );
							}
						} else {
							self::$logger->warning( sprintf(  '%s returned an unknown data format for %s message.', $data['serial_number'], $data['type'] ) );
						}
					} else {
						self::$logger->warning( sprintf(  '%s returned an unknown data format for %s message.', $data['serial_number'], $data['type'] ) );
					}
				} else {
					self::$logger->warning( sprintf(  '%s returned an unknown data format for %s message.', $data['serial_number'], $data['type'] ) );
				}
				break;
			case 'device_status':
				if ( array_key_exists( 'hub_sn', $data ) ) {
					$result['hub'] = $data['hub_sn'];
				}
				$id = substr( $data['serial_number'], 0, 2 );
				if ( array_key_exists( $id, $this->sensor_status ) && array_key_exists( 'sensor_status', $data ) ) {
					$sensors = (int) $data['sensor_status'];
					foreach ( $this->sensor_status[ $id ] as $key => $sensor ) {
						$result[ $sensor[0] . '_sensor' ] = ( 1 === $sensors & $key ? $sensor[1] : 'ok' );
					}
				}
				break;
			case 'hub_status':
				if ( array_key_exists( 'reset_flags', $data ) ) {
					$flags = explode( ',', $data['reset_flags'] );
					foreach( $this->reset_flags as $key => $flag ) {
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
		if ( array_key_exists( $data['type'], $this->fields ) && is_array( $this->fields[ $data['type'] ] ) && count( $this->fields[ $data['type'] ] ) === count( $d ) ) {
			for ( $i = 0; $i < count( $d ); $i++ ) {
				$result[ $this->fields[ $data['type'] ][ $i ] ] = $d[ $i ];
			}
		} else {
			self::$logger->warning( sprintf(  '%s returned an unknown data format for % message.', $data['serial_number'], $data['type'] ) );
		}
		foreach ( $this->forgot_fields as $field ) {
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
			$tagline = $this->merge_items( [], $this->static_tags, $data['serial_number'] );
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
			$tagline = $this->merge_items( [], $this->static_tags, $data['serial_number'] );
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
			$tagline                = $this->merge_items( $tags, $this->static_tags, $data['serial_number'] );
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
		$tagline = $this->merge_items( $this->get_tags( $data ), $this->static_tags, $data['serial_number'] );
		if ( '' !== $tagline ) {
			$result .= ',' . $tagline;
		}
		$fieldline = $this->merge_items( $this->get_values( $data ), $this->static_fields, $data['serial_number'] );
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
				$this->stat['dropped']++;
				return;
			}
		} catch ( \Throwable $e ) {
			$this->stat['dropped']++;
			return;
		}
		if ( array_key_exists( 'type', $d ) && array_key_exists( 'serial_number', $d ) ) {
			$device = $this->get_device( $d );
			if ( ! in_array( $device['id'], self::$devices ) ) {
				self::$devices[] = $device['id'];
				self::$logger->notice( sprintf ( 'New %s detected: %s', $device['type'], $device['id'] ), [ 'code' => 666 ] );
			}
			if ( 'observation' === self::$running_mode ) {
				return;
			} else {
				if ( in_array( $d['type'], $this->filters ) ) {
					$lines[] = $this->format_line( $d );
					if ( $device['is_hub'] ) {
						$lines = array_merge( $lines, $this->special_lines( $d ) );
					}
					foreach ($lines as $line ) {
						try {
							if ( isset( $this->influx ) ) {
								$this->influx->write( $line );
								$this->stat['sent']++;
							} else {
								$this->stat['unsent']++;
							}
						} catch ( \Throwable $e ) {
							$this->stat['unsent']++;
							preg_match('/"message":"(.*)("\}|\(truncated...\))/iU', $e->getMessage(), $matches);
							if ( 2 < count( $matches ) && '' !== $matches[1] ) {
								$message = str_replace( '\"', '"', $matches[1] );
								if ( str_contains( $matches[2], 'truncated' ) ) {
									$message .= '...';
								} else {
									$message .= '.';
								}
							} else {
								$message = $e->getMessage();
							}
							self::$logger->warning( 'Unable to write a record: ' . $message, [ 'code' => $e->getCode() ] );
						}
					}
					$this->stat['processed']++;
				} else {
					$this->stat['dropped']++;
				}
			}
		} else {
			$this->stat['dropped']++;
			return;
		}
	}

	/**
	 * Start the engine.
	 *
	 * @since   2.0.0
	 */
	private function inform() {
		self::$logger->notice( sprintf( 'Starting %s v%s in %s mode.', WF_NAME, WF_VERSION, self::$running_mode ) );
		if ( $this->strict_isu ) {
			self::$logger->warning( 'Strict-ISU activated.' );
		}
	}

	/**
	 * Prints the stats.
	 *
	 * @since   2.0.0
	 */
	private function stats() {
		$tmp = [];
		foreach ( $this->stat as $key => $count ) {
			$tmp[] = $key . '=' . $count;
		}
		$duration = new Duration( time() - $this->start_time );
		self::$logger->info( sprintf( 'Stats: %s in %s.', implode( ', ', $tmp ), $duration->humanize() ) );
	}

	/**
	 * Start the engine.
	 *
	 * @since   1.0.0
	 */
	private function start() {
		$this->inform();
		try {
			$ws_worker = new Worker( 'udp://0.0.0.0:50222' );
			$ws_worker->count = 1;
		} catch ( \Throwable $e ) {
			self::$logger->emergency( sprintf( 'Unable to create worker: %s.', $e->getMessage() ), [ 'code' => $e->getCode() ] );
			$this->abort( 2 );
		}
		$ws_worker->onWorkerStart = function( $worker ) {
			Timer::add(self::$conf_reload, function () {
				$this->get_options();
			} );
			Timer::add(self::$statistics, function () {
				$this->stats();
			} );
			self::$logger->notice( 'Worker launched.' );
			self::$logger->debug( 'Started listening on UDP port 50222.' );
			$this->start_time = time();
		};
		$ws_worker->onWorkerStop = function( $worker ) {
			self::$logger->debug( 'Stopped listening on UDP port 50222.' );
			$this->stats();
			self::$logger->notice( 'Worker stopped.' );
		};
		$ws_worker->onError = function( $connection, $code, $msg ) {
			self::$logger->error( $msg, [ 'code' => $code ]);
		};
		$ws_worker->onMessage = function ( $connection, $data ) {
			$this->process( $data );
		};
		try {
			Worker::runAll();
		} catch ( \Throwable $e ) {
			self::$logger->emergency( sprintf( 'Unable to launch worker: %s.', $e->getMessage() ), [ 'code' => $e->getCode() ] );
			$this->abort( 3 );
		}
	}

	/**
	 * Create an instance if needed, then run it.
	 *
	 * @param   string   $config    Configuration file name.
	 * @param   boolean  $docker    True if executed in a Docker container.
	 * @since   1.0.0
	 */
	public static function run( $config, $docker ) {
		if ( ! isset( self::$engine ) ) {
			self::init();
			self::$config = $config;
			self::$docker = $docker;
			self::$engine = new Engine();
			self::$engine->start();
		}
	}

	/**
	 * Initializes class.
	 *
	 * @since   2.0.0
	 */
	private static function init() {
		global $argv;
		self::$logger = new Logger('weatherflux', [ new ConsoleHandler() ] );
		if ( self::$docker ) {
			self::$logger->pushHandler( new DockerConsoleHandler() );
		}
		self::$logger->notice( 'Initializing ' . WF_NAME );
		if ( getenv( 'WF_CONF_RELOAD' ) ) {
			$conf_reload = (int) getenv( 'WF_CONF_RELOAD' );
			if ( $conf_reload > self::$conf_reload ) {
				self::$conf_reload = $conf_reload;
				self::$logger->debug( sprintf( 'Configuration reloading interval overridden. New value: %ds.',  self::$conf_reload ) );
			}
		}
		if ( getenv( 'WF_STAT_PUBLISH' ) ) {
			$statistics = (int) getenv( 'WF_STAT_PUBLISH' );
			if ( $statistics > self::$statistics ) {
				self::$statistics = $statistics;
				self::$logger->debug( sprintf( 'Statistics publishing interval overridden. New value: %ds.',  self::$statistics ) );
			}
		}
		if ( in_array('-c', $argv, true ) ) {
			$argv[] = '-q';
			if ( in_array( '-d', $argv ) ) {
				$argv = array_diff( $argv, ['-d'] );
			}
			self::$running_mode = 'console';
		}
		if ( in_array('-o', $argv, true ) ) {
			$argv[] = '-q';
			if ( in_array( '-d', $argv ) ) {
				$argv = array_diff( $argv, ['-d'] );
			}
			self::$running_mode = 'observation';
		}
		if ( in_array('-d', $argv, true ) ) {
			self::$running_mode = 'daemon';
		}
		self::$logger->debug( sprintf( 'Configuration reloading: %ds.', self::$conf_reload ) );
		self::$logger->debug( sprintf( 'Statisctics publishing: %ds.', self::$statistics ) );
	}

	/**
	 * Verify if all is ok.
	 *
	 * @param   string   $config    Configuration file name.
	 * @param   boolean  $docker    True if executed in a Docker container.
	 * @since   2.0.0
	 */
	public static function healthcheck( $config, $docker ) {

	}
}