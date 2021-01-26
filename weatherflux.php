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

define( 'WF_NAME', 'WeatherFlux' );
define( 'WF_VERSION', '2.0.0' );

error_reporting(0);

$docker = file_exists( '/.dockerenv ');

if ( $docker ) {
	if ( file_exists( __DIR__ . '/../../../vendor/autoload.php' ) ) {
		require_once __DIR__ . '/../../../vendor/autoload.php';
	} else {
		exit( 1 );
	}
	if ( file_exists( __DIR__ . '/../../../config/config.json' ) ) {
		$config = __DIR__ . '/../../../config/config.json';
	} else {
		$config = '';
	}
} else {
	if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
		require_once __DIR__ . '/vendor/autoload.php';
		require_once __DIR__ . '/src/autoload.php';
	} else {
		exit( 1 );
	}
	if ( file_exists( __DIR__ . '/config.json' ) ) {
		$config = __DIR__ . '/config.json';
	} else {
		$config = '';
	}
}

if ( in_array('status', $argv, true ) ) {
	\WeatherFlux\Engine::status( $config, $docker, in_array('-h', $argv, true ) );
} elseif ( in_array('stop', $argv, true ) ) {
	\WeatherFlux\Engine::stop( $config, $docker);
} else {
	\WeatherFlux\Engine::run( $config, $docker );
}

