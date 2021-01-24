# Fields units and meaning

Data processed by WeatherFlux is expressed with units from [ISU](https://en.wikipedia.org/wiki/International_System_of_Units). It may involve conversion regarding the `unit-system` option set in `config.json`.

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