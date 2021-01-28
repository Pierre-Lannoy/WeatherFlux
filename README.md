# WeatherFlux

WeatherFlux is a simple gateway that listen on the local network to messages sent by WeatherFlow station(s) and send them to an [InfluxDB 2.x](https://www.influxdata.com/products/influxdb/) server.

You can run WeatherFlux on:
* any system supporting Docker version 19 or greater (WeatherFlux dockerized version);
* any POSIX compatible operating system (Linux, OSX, BSD) supporting PHP 7.4 or greater and composer 2.0 or greater (WeatherFlux standalone version).

## WeatherFlux on Docker
![Docker Image Version (tag latest semver)](https://img.shields.io/docker/v/pierrelannoy/weatherflux/latest?style=flat-square)
![Docker Cloud Build Status](https://img.shields.io/docker/cloud/build/pierrelannoy/weatherflux?style=flat-square)
![Docker Image Size (tag)](https://img.shields.io/docker/image-size/pierrelannoy/weatherflux/latest?style=flat-square)

To run WeatherFlux on Docker, just type:

```
docker run -itdp 50222:50222/udp -v /my/local/path:/usr/share/weatherflux/config pierrelannoy/weatherflux:latest
```

where `/my/local/path` is a valid path on the host.

Then, [adjust the settings](https://github.com/Pierre-Lannoy/WeatherFlux/blob/master/CONFIG.md) in the `/my/local/path/config.json` file.


## WeatherFlux as standalone tool
![Packagist Version](https://img.shields.io/packagist/v/weatherflux/weatherflux?style=flat-square)
![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/weatherflux/weatherflux?style=flat-square)
![Packagist License](https://img.shields.io/packagist/l/weatherflux/weatherflux?style=flat-square)

If you don't want - or can't - use the dockerized version of WeatherFlux, you can use it as a standalone tool. If so, installing WeatherFlux is simple as:

```
composer require weatherflux/weatherflux
```

Then, creating a configuration file named `config.json`:

```console
mkdir ./config && cp ./vendor/weatherflux/weatherflux/config-blank.json ./config/config.json
```

[Adjust the settings](https://github.com/Pierre-Lannoy/WeatherFlux/blob/master/CONFIG.md) in this newly created file to match your environment and your needs.

### Running WeatherFlux as standalone tool

To just listen the local network and display discovered devices (without recording anything), start WeatherFlux in **o***bserver* mode

```console
php vendor/weatherflux/weatherflux/weatherflux.php start -o
```

To listen the local network and send data to InfluxDB (with console output), start WeatherFlux in **c***onsole* mode

```console
php vendor/weatherflux/weatherflux/weatherflux.php start -c
```

To listen the local network and send data to InfluxDB (unattended), start WeatherFlux in **d***aemon* mode

```console
php vendor/weatherflux/weatherflux/weatherflux.php start -d
```

If WeatherFlux is started in daemon mode, you can stop it as follow:

```console
php vendor/weatherflux/weatherflux/weatherflux.php stop
```

For other modes, just hit `CTRL+C` to stop it.

