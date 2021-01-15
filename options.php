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

$options = [
	'logging' => [

	],



	/* ==========================================================
	 * == List of message types that MUST be parsed and stored ==
	 * ==========================================================
	 *
	 * It's a pass-through filter so, only messages from these type will be sent to InfluxDB.
	 * Values: evt_precip / evt_strike / rapid_wind / obs_air / obs_sky / obs_st / device_status / hub_status
	 *
	 */
	'filters' => [
		'evt_precip',
		'evt_strike',
		'rapid_wind',
		'obs_air',
		'obs_sky',
		'obs_st',
		//'device_status',
		//'hub_status',
	],



	/* =============================================
	 * == Tags to add for each stored measurement ==
	 * =============================================
	 *
	 * You can specify tags for all measurements ("*" key) or for specific devices (serial_number key).
	 * Tags must be strings. You can add as many tags you want.
	 *
	 */
	'tags'    => [
		'*' => [
			'host'     => \gethostname(),
			'location' => 'Mouvaux - France'
		],
		'HB-00003922'   => [
			'station'   => 'weather',
			'model'     => 'WeatherFlow Hub',
			'placement' => 'indoor',
			'cluster'   => 'station 1',
		],
		'AR-00012626'   => [
			'station'   => 'weather',
			'model'     => 'WeatherFlow Air',
			'placement' => 'outdoor',
			'cluster'   => 'station 1',
		],
		'AR-00007949'   => [
			'station'   => 'weather',
			'model'     => 'WeatherFlow Air',
			'placement' => 'indoor',
			'cluster'   => 'station 1',
		],
		'SK-00000700'   => [
			'station'   => 'weather',
			'model'     => 'WeatherFlow Sky',
			'placement' => 'outdoor',
			'cluster'   => 'station 1',
		],
	],


	/* ======================================================
	 * == Static fields to add for each stored measurement ==
	 * ======================================================
	 *
	 * You can specify static fields (i.e. fields that have a static value) for all measurements ("*" key) or for
	 * specific devices (serial_number key).
	 * Static fields must be numerical. You can add as many static fields you want.
	 *
	 */
	'fields'    => [
		'*' => [
			'latitude'  => 50.7045,
			'longitude' => 3.1354,
			'altitude'  => 55,
		],
		'SK-00000700'   => [
			'altitude'  => 60,
		],
	],
];

//SK-00000700