# WeatherFlux
![version](https://badgen.net/github/release/Pierre-Lannoy/WeatherFlux/)
![php](https://badgen.net/badge/php/7.4+/green)
![license](https://badgen.net/github/license/Pierre-Lannoy/WeatherFlux/)

WeatherFlux is a simple gateway that listen on the local network for messages sent by WeatherFlow station(s) and send them to an [InfluxDB 2.x](https://www.influxdata.com/products/influxdb/) server.

You can run WeatherFlux on any POSIX compatible operating system (Linux, OSX, BSD) as a console tool or a deamon.

## Installing WeatherFlux

Installing WeatherFlux is simple as:

```
composer require weatherflux/weatherflux
```

## Configuring WeatherFlux

Start by creating a new configuration file named `config.php`:

```console
pierre@dev:~$ cp vendor/weatherflux/weatherflux/config-sample.php vendor/weatherflux/weatherflux/config.php
```

Then, edit this file to match your environment and your needs.

If you simply set `influxb` parameters in this file, all WeatherFlow messages will be sent to your InfluxDB instance with maximum level/fields details.

## Running WeatherFlux

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

## Fields units and meaning

Data processed by WeatherFlux is expressed with units from [ISU](https://en.wikipedia.org/wiki/International_System_of_Units). It may involve conversion regarding the `unit-system` option set in `config.php`.

| Field | Strict ISU | Derived ISU | Meaning |  Note |
| --- | :---: | :---: | --- | --- |
| `illuminance_sun` | cd⋅m²⋅m⁻⁴ | lux | Solar illuminance |  |
| `irradiance_sun` | kg⋅s⁻³ | W⋅m⁻² | Solar irradiance |  |
| `pressure_station` | kg⋅m⁻¹⋅s⁻² | Pa | Atmospheric pressure (station level) |  |
| `r-humidity` | % | % | Atmospheric relative humidity |  |
| `rain_accumulation` | m | m | Accumulated precipitation since ? |  |
| `report_interval` | s | s | Time between two reports |  |
| `rssi_self` | dB | dB | Received signal strength (device perceived) |  |
| `rssi_hub` | dB | dB | Received signal strength (device perceived) |  |
| `strike_count` | - | - | Number of strike since ? |  |
| `strike_distance` | m | m | Approx. distance of last strike |  |
| `strike_energy` | - | - | Undocumented |  |
| `temperature_air` | K | °C | Atmospheric temperature | Conversion involved: K ↔ °C |
| `uptime` | s | s | Uptime of the device |  |
| `uv` | - | - | UV index | Open-ended linear scale |
| `voltage` | kg⋅m²⋅s⁻³⋅A⁻¹ | V | Voltage of the device's battery |  |
| `wind_average` | m⋅s⁻¹ | m⋅s⁻¹ | Wind average (over report interval) |  |
| `wind_direction` | m/m | ° | From source to destination | Conversion involved: rad ↔ ° |
| `wind_gust` | m⋅s⁻¹ | m⋅s⁻¹ | Wind gust (maximum 3 second sample) |  |
| `wind_lull` | m⋅s⁻¹ | m⋅s⁻¹ |Wind lull (minimum 3 second sample) |  |
| `wind_sample_interval` | s | s | Sampling duration | Not available for `wind_speed` |
| `wind_speed` | m⋅s⁻¹ | m⋅s⁻¹ | Instant wind speed |  |

Status (device, radio, file system, etc.) fields or undocumented fields are stored "as is", without units definition and without conversion.