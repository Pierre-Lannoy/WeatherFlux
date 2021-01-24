# Configuring WeatherFlux

The configuration of WeatherFlux is done by settings parameter in the file `config.json`. This file can be found:

* in the host-mounted `/usr/share/weatherflux/config`volume, if ran in Docker;
* in the `./vendor/weatherflux/weatherflux/` directory if ran as standalone tool.

You don't have to restart WeatherFlux when modifying its settings: the configuration file is automatically reloaded every 2 minutes by default. If you want to modify this interval, please read [Environment Variables](#environment-variables) section.

> 💡 If you simply set the 4 `influxb` parameters in this file, all WeatherFlow messages will be sent to your InfluxDB instance with maximum level/fields details - without you having to specify anything additional.


## Environment variables

### `WF_CONF_RELOAD` variable

To change the time interval between two automatic reloads of the configuration file, you can give it a value in seconds.

```console
pierre@dev:~$ export WF_CONF_RELOAD=300
```

If you do not specify anything, the default value is 120 seconds.

### `WF_STAT_PUBLISH` variable

-