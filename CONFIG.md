# Configuring WeatherFlux

The configuration of WeatherFlux is done by setting parameters in the file `config.json`. This file can be found:

* in the host-mounted `/usr/share/weatherflux/config`volume, if ran in Docker;
* in the `./config/` directory if ran as standalone tool.

In most cases, you will not have to restart WeatherFlux when modifying its settings: the configuration file is automatically reloaded every 2 minutes by default. If you want to modify this interval, please read [Environment Variables](#environment-variables) section.

> 💡 If you simply set the 4 `influxb` parameters in this file, all WeatherFlow messages will be sent to your InfluxDB instance with maximum level/fields details - without you having to specify anything additional.

## Settings

The configuration file is json formatted and contains  

### `influxb` section

In this mandatory section, you MUST define the 4 parameters needed to connect to your InfluxDB instance.
```json
"influxb": {
  "url":"http://localhost:8086",
  "org":"my-org",
  "token":"my-token",
  "bucket":"my-bucket"
},
```

### `logging` section

*This section is not mandatory and not used for now.*

### `host` section

This mandatory section allows to specify how and if the hostname must be part of the sent record.

```json
"host": {
  "override":"",
  "drop":false
},
```

If the `override` parameter is empty, the local hostname is used. If you set something in this string, this value will be used instead.

To avoid sending this value to InfluxDB, just set the `drop` parameter to true.

### `isu-mode` section

The mandatory `isu-mode` parameter can take only one of this values:

- `strict` to use only [strict ISU units](https://github.com/Pierre-Lannoy/WeatherFlux/blob/master/ISU.md) (it involves conversions for temperatures and angles);
- `derived` to use [derived ISU units](https://github.com/Pierre-Lannoy/WeatherFlux/blob/master/ISU.md).

I recommend you, for better visualization of meteorological time series, to use derived ISU units.

> ⚠️ If you change this parameter when you have already collected data in your bucket, the time series will appear distorted when visualized.

### `filters` section

This mandatory section lets you tell to WeatherFlux which Weatherflow message types to process:

- "evt_precip": sent when precipitation occurs;
- "evt_strike": sent when a strike is detected;
- "rapid_wind": sent every 3s or 15s with wind conditions;
- "obs_air": sent every minute by Air module (all measurements);
- "obs_sky": sent every minute by Sky module (all measurements);
- "obs_st": sent every minute by Tempest module (all measurements);
- "device_status": sent every minute by all modules (status);
- "hub_status": sent every minute by all hubs (status);

A correct `filters` section could be:

```json
"filters":["evt_precip","evt_strike","rapid_wind","obs_air","obs_sky","obs_st"],
```

Note all unspecified types will be dropped by WeatherFlux.

### `tags` section

### `fields` section

## Measurement names

Names for measurements are under the control of WeatherFlux. They can not be modified.

Each measurement name is formatted following a `<device_id>_<data_type>` convention:

![measurement names sample](https://github.com/Pierre-Lannoy/WeatherFlux/blob/master/medias/measurements.jpg "measurement names sample")

## Environment variables

### `WF_CONF_RELOAD`

To change the time interval between two automatic reloads of the configuration file, you can give it a value in seconds.

```console
pierre@dev:~$ export WF_CONF_RELOAD=300
```

If you do not declare this variable, the default value used by WeatherFlux is 120 seconds.

### `WF_STAT_PUBLISH`

-