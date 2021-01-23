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

	/* ==========================================================
	 * == InfluxDB2 connexion parameters                       ==
	 * ==========================================================
	 *
	 */
	'influxb' => [
		'url' => 'http://localhost:8086',
		'org' => 'my-org',
		'token' => 'my-token',
		'bucket' => 'my-bucket',
	],



	/* ===========================================
	 * == Unit system in which data is reported ==
	 * ===========================================
	 *
	 * Do the units have to be chosen only in strict ISU (only base units) or in derived ISU too.
	 * Tips: it's more "readable" to use derived ISU for weather data.
	 * Values: strict / derived
	 *
	 */
	'unit-system' => [
		'derived',
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
		'device_status',
		'hub_status',
	],



	/* ====================================================
	 * == Static tags to add for each stored measurement ==
	 * ====================================================
	 *
	 * You can specify static tags (i.e. tags that have a static value) for all measurements ("*" key) or for
	 * specific devices (serial_number key).
	 * Tags must be strings. You can add as many tags you want.
	 *
	 */
	'tags'    => [
		'*' => [
			'host'     => (string) \gethostname(),
			'location' => 'My Location'
		],
	],


	/* ======================================================
	 * == Static fields to add for each stored measurement ==
	 * ======================================================
	 *
	 * You can specify static fields (i.e. fields that have a static value) for all measurements ("*" key) or for
	 * specific devices (serial_number key).
	 * Fields must be numerical. You can add as many static fields you want.
	 *
	 */
	'fields'    => [

	],
];