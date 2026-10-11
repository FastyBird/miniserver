# Shelly Devices Scripting

Generation 2 of Shelly devices support custom [scripts](https://shelly-api-docs.shelly.cloud/gen2/Scripts/Tutorial).
One use case could be calculation of temperature measured by NTC thermistor.

## Create script in device

In this example we will use [Shelly Plus 1PM](https://www.shelly.com/en-cz/products/product-overview/shelly-plus-1-pm) and [Shelly Plus Add-on](https://www.shelly.com/en-cz/products/product-overview/shelly-plus-add-on)
with connected NTC sensor. After hardware connection you will have to create new script in the device to calculate temperature from measured voltage.

<img alt="Scripts overview" src="_media/scripts_overview.png" />

Now you could add new script which will read measured voltage from NTC thermistor and calculate temperature. You could find
the script in the Shelly scripts library

<img alt="Create script" src="_media/scripts_create.png" />

Here is a example of the calculation script:

```js
// SH Coefficient Calculator
// https://rusefi.com/Steinhart-Hart.html
//
// Thermistor wiki page
// https://en.wikipedia.org/wiki/Thermistor

/**************** START CHANGE HERE ****************/
let CONFIG = {
  scanInterval: 30, //secs, this will run a timer for every 30 seconds, that will fetch the voltage
  voltmeterID: 100, //the ID of the voltmeter - When we install the add on, the device will define this number

  /**
   * Applies some math on the voltage and returns the result. This function is called every time the voltage is measured
   * @param {Number} voltage The current measured voltage
   * @returns The temperature based on the voltage
   */
  calcTemp: function (voltage) {
    const constVoltage = 10;
    const R1 = 10000;
    const A = 0.0010351024725298568;
    const B = 0.0002338353574079741;
    const C = 7.917851073336009e-8;

    const R2 = R1 * (voltage / (constVoltage - voltage));
    const logR2 = Math.log(R2);

    let T = 1.0 / (A + (B + C * logR2 * logR2) * logR2);
    T = T - 273.15;

    return Math.round(T * 100) / 100;
  },

  /**
   * This function is called every time when a temperature is read
   * @param {Number} temperature The current calculated temperature
   */
  onTempReading: function (temperature) {
    console.log('Measured temp:', temperature);

    if (temperature > 23) {
      Shelly.call("Switch.Set", {
        id: 0,
        on: false,
      });
    }

    Shelly.emitEvent("result", temperature);
  },
};
/**************** STOP CHANGE HERE ****************/

function fetchVoltage() {
  //Fetch the voltmeter component
  const voltmeter = Shelly.getComponentStatus(
    "voltmeter:" + JSON.stringify(CONFIG.voltmeterID)
  );

  //exit if can't find the component
  if (typeof voltmeter === "undefined" || voltmeter === null) {
    console.log("Can't find the voltmeter component");
    return;
  }

  const voltage = voltmeter["voltage"];

  //exit if can't read the voltage
  if (typeof voltage !== "number") {
    console.log("can't read the voltage or it is NaN");
    return;
  }

  //get the temperature based on the voltage
  const temp = CONFIG.calcTemp(voltage);

  //exit if the temp isn't calculated correctly
  if (typeof temp !== "number") {
    console.log("Something went wrong when calculating the temperature");
    return;
  }

  if (typeof CONFIG.onTempReading === "function") {
    CONFIG.onTempReading(temp);
  }
}

//init the script
function init() {
  //start the timer
  Timer.set(CONFIG.scanInterval * 1000, true, fetchVoltage);

  //fetch the voltage at run
  fetchVoltage();
}

init();
```

Method `onTempReading` is a custom call back:

```js
  onTempReading: function (temperature) {
    console.log('Measured temp:', temperature);

    if (temperature > 23) {
      Shelly.call("Switch.Set", {
        id: 0,
        on: false,
      });
    }

    Shelly.emitEvent("result", temperature);
  }
```

This part will check measured temperature and if is higher it will turn of the Shelly Plus 1PM device. And on every data reading
will trigger event with result. This value will be then used by [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) system.

After saving script you will be able to enable it and start.

<img alt="Scripts list" src="_media/scripts_list.png" />

## Use script in FastyBird IoT system

So now is you device ready and script is running. You have to run devices discovery and [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) system
will find scripts configuration and create configuration in system.

If the script trigger an event: `Shelly.emitEvent("result", temperature);` this event will be caught by system and stored in special
channel property with name `Result`. This property is by default configured as `String` property, so you will have to edit this property
via system and change data type to `Float` and additionally configure a unit.

And that is all, now you will se result of this script in [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) and use it to interact with other devices.