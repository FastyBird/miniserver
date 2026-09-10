# Configuration

To integrate [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem devices
with Thermostat Device Addon, you will need to configure at least one virtual connector. How to configure Virtual connector,
please refer to [Virtual connector manual](https://github.com/FastyBird/virtual-connector/wiki).

The thermostat device can be configured using the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things)
user interface or through the console.

## Configuring the Thermostats, Actors, Sensors and Preset through the Console

To configure the thermostat device through the console, run the following command:

```shell
php bin/fb-console fb:virtual-thermostat-addon:install
```

> [!NOTE]
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.

```
Thermostat Device addon - installer
===================================

 ! [NOTE] This action will create|update|delete addon configuration

 What would you like to do? [Nothing]:
  [0] Create thermostat
  [1] Edit thermostat
  [2] Delete thermostat
  [3] Manage thermostat
  [4] List thermostats
  [5] Nothing
 > 0
```

### Create thermostat

When opting to create a new thermostat, you'll be prompted to select connector and to provide a thermostat basic configuration:

```
 Please select connector to manage [virtual-1]:
  [0] virtual-1
 > 
```

```
 Provide thermostat identifier:
 > living-room-thermostat
```

```
 Provide thermostat name:
 > Living room thermostat
```

Now you will be asked to provide configuration:

```
 Please select thermostat supported modes (multiple answers available) [Heating]:
  [0] Heating
  [1] Cooling
  [2] Auto
 > 
```

> [!TIP]
To select multiple values just enter value numbers separated by commas, e.g. > 0,1,2

- **Heater** - Used for heating
- **Cooler** - Used for cooling
- **Auto** - Combining both modes to automatically heat or cool

```
 Provice thermostat units [°C]:
  [0] °C
  [1] °F
 > 
```

> [!TIP]
Ensure consistency by utilizing a single unit type across all values and mapped properties to prevent potential issues.

#### Heaters mapping

In the subsequent steps, you will be prompted to configure thermostat actors.

For instance, if you selected in the previous step that the thermostat supports heating, you will then configure heater actors.

```
 [INFO] Configure thermostat heater actor/s. This device/s will be controled by thermostat according to settings


 Select device for mapping:
  [0] [Hardware connector] Hardware device 01
  [1] [Hardware connector] Hardware device 02
  [2] [Hardware connector] Hardware device 03
  [3] [Hardware connector] Hardware device 04
  [4] [Hardware connector] Hardware device 05
 > 
```

> [!NOTE]
The displayed list of devices may differ based on the connectors and registered devices within the system.

While mapping the physical device to the thermostat actor, you will be prompted to choose a channel and its corresponding
property. The thermostat will subsequently regulate the state of this property based on thermostat conditions.

```
 Select device channel for mapping:
  [0] Device inputs
  [1] Device outpus
 > 
```

```
 Select channel property for mapping [State]:
  [0] State
 > 
```

> [!NOTE]
The displayed lists above will only showcase supported devices that can be mapped to thermostat actors.

Each thermostat could support multiple actors.

```
 Do you want to add another heater actor? (yes/no) [no]:
 > 
```

If this thermostat is supporting **colling mode**, you will be asked to configure cooler actors:

```
 [INFO] Configure thermostat cooler actor/s. This device/s will be controled by thermostat according to settings


 Select device for mapping:
  [0] [Hardware connector] Hardware device 01
  [1] [Hardware connector] Hardware device 02
  [2] [Hardware connector] Hardware device 03
  [3] [Hardware connector] Hardware device 04
  [4] [Hardware connector] Hardware device 05
 > 
```

#### Coolers mapping

Process of cooler mapping is same as heater mapping.

#### Windows openings sensors mapping

It is better to turn off heating or cooling when windows or doors are opened. For this case this thermostat is supporting
openings sensors configuration.

```
 Do you want to configure openings sensors? Like windows, doors, etc. (yes/no) [no]:
 >
 
 Select device for mapping:
  [0] [Hardware connector] Hardware device 01
  [1] [Hardware connector] Hardware device 02
  [2] [Hardware connector] Hardware device 03
  [3] [Hardware connector] Hardware device 04
  [4] [Hardware connector] Hardware device 05
 > 
```

The process i similar to previous steps. You will have to select device, its chanel and binary property which will represent
openings sensor state.

Once this sensor propagate **open** state thermostat will automatically turn off all actors to prevent energy wasting.

#### Temperature sensors mapping

This sensor is crucial for thermostat. You have to use temperature sensors which are able to provide valid temperature value.
This value will be used by thermostat to calculate its state. E.g. if temperature is low, thermostat will then turn on heaters.

```
 [INFO] Configure thermostat temperature sensor/s. This device/s will report values to thermostat


 Select device for mapping:
  [0] [Hardware connector] Hardware device 01
  [1] [Hardware connector] Hardware device 02
  [2] [Hardware connector] Hardware device 03
  [3] [Hardware connector] Hardware device 04
  [4] [Hardware connector] Hardware device 05
 > 
```

```
 Select device channel for mapping:
  [0] Room temperature
  [1] Floor temperature
 > 
```

```
 Select channel property for mapping:
  [0] Temperature Celsius
  [1] Temperature Fahrenheit
 > 
```

> [!NOTE]
This thermostat supports both Celsius and Fahrenheit units, but it is essential to ensure consistent usage of a single unit across all sensors and configurations.

You can define as many sensors as necessary. When multiple sensors are utilized, the thermostat will calculate the
average value, which will be employed in subsequent calculations.

```
 Do you want to add another temperature sensor? (yes/no) [no]:
 > 
```

#### Floor sensors mapping

In scenarios where the thermostat is employed for electric floor heating, it is advisable to utilize a floor temperature
sensor. This precaution helps prevent the floor from overheating.

```
 Do you want to configure floor temperature sensors? (yes/no) [no]:
 > y
```

The mapping process is straightforward. You simply need to choose a device from the given list and then specify the
channel where the state property is located.

```
 Select device for mapping:
  [0] [Hardware connector] Hardware device 01
  [1] [Hardware connector] Hardware device 02
  [2] [Hardware connector] Hardware device 03
  [3] [Hardware connector] Hardware device 04
  [4] [Hardware connector] Hardware device 05
 > 
```

```
 Select device channel for mapping:
  [0] Room temperature
  [1] Floor temperature
 > 
```

```
 Select channel property for mapping:
  [0] Temperature Celsius
  [1] Temperature Fahrenheit
 > 
```

You can define as many sensors as necessary. When multiple sensors are utilized, the thermostat will calculate the
average value, which will be employed in subsequent calculations.

```
 Do you want to add another floor temperature sensor? (yes/no) [no]:
 > 
```

#### Thermostat modes configuration

When configuring a thermostat with floor sensors, you will be prompted to set the maximum floor temperature to prevent overheating.

```
 Provide maximum allowed floor temperature (°C) [28]:
 > 28
```

In the next steps, you will be guided to configure thermostat presets. This thermostat offers predefined presets,
and you have the flexibility to choose any of them according to your preferences.

```
 Please select thermostat supported presets (multiple answers available) [Away, Eco, Home, Comfort, Sleep, Anti freeze]:
  [0] Away
  [1] Eco
  [2] Home
  [3] Comfort
  [4] Sleep
  [5] Anti freeze
  [6] None
 > 
```

Once presets are selected you will be asked to configure each preset individually.

The target temperature for the thermostat in selected preset mode determines the desired temperature. In the selected mode,
the thermostat works to heat or cool down to this specific target temperature.

```
 Provide target temperature value for manual mode (°C):
 > 20
```

When setting up the thermostat for **automatic mode**, it is necessary to configure both heating and cooling thresholds.
If the current temperature falls below the heating threshold, the thermostat activates heating actors until the target
temperature is achieved, and then the actors are turned off. Conversely, if the temperature surpasses the cooling threshold,
the thermostat activates cooling actors until the target temperature is reached, and then the actors are deactivated.

```
 Provide heating threshold temperature (°C):
 > 19
```

```
 Provide cooling threshold temperature (°C):
 > 21
```

> [!TIP]
For selecting multiple values, enter the corresponding numbers separated by commas, such as > 0,1,2. Alternatively,
if you prefer not to use thermostat presets, simply choose **None**, and your thermostat will operate solely in manual mode.

## Configuring the Thermostat with the FastyBird User Interface

You can also configure the Thermostat devices using the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface. For more information
on how to do this, please refer to the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) [documentation](https://miniserver.fastybird.com/docs).
