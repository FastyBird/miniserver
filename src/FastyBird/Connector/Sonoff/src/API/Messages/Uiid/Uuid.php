<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\API\Messages\Uiid;

use FastyBird\Connector\Sonoff\API;

/**
 * UIID base interface
 */
interface Uuid extends API\Messages\Message
{

	public const string VALUE_ON = 'on';

	public const string VALUE_OFF = 'off';

	public const string VALUE_STAY = 'stay';

	public const string VALUE_START = 'start';

	public const string VALUE_STOP = 'stop';

	public const string VALUE_GET = 'get';

	public const string TEMPERATURE_COLD = 'cold';

	public const string TEMPERATURE_MIDDLE = 'middle';

	public const string TEMPERATURE_WARM = 'warm';

	public const string SPEED_SLOW = 'slow';

	public const string SPEED_MODERATE = 'moderate';

	public const string SPEED_FAST = 'fast';

	public const string MODE_NORMAL = 'normal';

	public const string MODE_NATURAL = 'natural';

	public const string MODE_SLEEP = 'sleep';

	public const string COMMAND_CAPTURE = 'capture';

	public const string COMMAND_CAPTURE_CANCEL = 'captureCancel';

	public const string COMMAND_EDIT = 'edit';

	public const string COMMAND_TRANSMIT = 'transmit';

	public const string COMMAND_TRIGGER = 'trigger';

	/**
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public function toStates(): array;

}
