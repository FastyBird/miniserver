<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Types\Payloads;

/**
 * Motor calibration payload types
 */
enum MotorCalibration: string implements Payload
{

	case CALIBRATION = 'calibration';

	case NORMAL = 'normal';

}
