# WeatherFlux
![version](https://badgen.net/github/release/Pierre-Lannoy/WeatherFlux/)
![license](https://badgen.net/github/license/Pierre-Lannoy/WeatherFlux/)

WeatherFlux is a simple gateway that listen on the local network for messages sent by WeatherFlow station(s) and send them to an [InfluxDB 2.x](https://www.influxdata.com/products/influxdb/) server.

You can run WeatherFlux on:
* any system supporting Docker version 19 or greater (WeatherFlux dockerized version);
* any POSIX compatible operating system (Linux, OSX, BSD) supporting PHP 7.4 or greater and composer 2.0 or greater (WeatherFlux standalone version).

## WeatherFlux on Docker

 *- coming soon -*

## WeatherFlux as standalone tool

If you don't want - or can't - use the dockerized version of WeatherFlux, you can use it as a standalone tool. If so, installing WeatherFlux is simple as:

```
composer require weatherflux/weatherflux
```

Then, creating a configuration file named `config.json`:

```console
pierre@dev:~$ cp vendor/weatherflux/weatherflux/config-sample.json vendor/weatherflux/weatherflux/config.json
```

[Adjust the settings](/CONFIG.md) in this newly created file to match your environment and your needs.

### Running WeatherFlux as standalone tool

To just listen the local network and display discovered devices (without recording anything), start WeatherFlux in **o***bserver* mode

```console
pierre@dev:~$ php vendor/weatherflux/weatherflux/weatherflux.php start -o
```

To listen the local network and send data to InfluxDB (with console output), start WeatherFlux in **c***onsole* mode

```console
pierre@dev:~$ php vendor/weatherflux/weatherflux/weatherflux.php start -c
```

To listen the local network and send data to InfluxDB (unattended), start WeatherFlux in **d***aemon* mode

```console
pierre@dev:~$ php vendor/weatherflux/weatherflux/weatherflux.php start -d
```

If WeatherFlux is started in daemon mode, you can stop it as follow:

```console
pierre@dev:~$ php vendor/weatherflux/weatherflux/weatherflux.php stop
```

For other modes, just hit `CTRL+C` to stop it.

