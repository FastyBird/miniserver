<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Types;

/**
 * Device category types
 */
enum Category: string
{

	case UNKNOWN = 'unknown';

	case PLUG = 'plug';

	case SWITCH = 'switch';

	case LIGHT = 'light';

	case CURTAIN = 'curtain';

	case CONTACT_SENSOR = 'contactSensor';

	case MOTION_SENSOR = 'motionSensor';

	case TEMPERATURE_SENSOR = 'temperatureSensor';

	case HUMIDITY_SENSOR = 'humiditySensor';

	case TEMPERATURE_HUMIDITY_SENSOR = 'temperatureAndHumiditySensor';

	case WATTER_LEAK_DETECTOR = 'waterLeakDetector';

	case SMOKE_DETECTOR = 'smokeDetector';

	case BUTTON = 'button';

	case CAMERA = 'camera';

	case SENSOR = 'sensor';

	case FAN_LIGHT = 'fanLight';

	case AIR_CONDITIONER = 'airConditioner';

	case FAN = 'fan';

	case THERMOSTAT = 'thermostat';

}
