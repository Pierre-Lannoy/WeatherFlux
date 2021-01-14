<?php

use WeatherFlux\Engine;
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/autoload.php';

define( 'WF_NAME', 'WeatherFlux' );
define( 'WF_VERSION', '1.0.0-dev' );

Engine::run();