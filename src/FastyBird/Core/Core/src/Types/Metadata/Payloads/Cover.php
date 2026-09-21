<?php declare(strict_types = 1);

/**
 * Cover.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           27.12.22
 */

namespace FastyBird\Core\Types\Metadata\Payloads;

/**
 * Cover/Roller supported payload types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum Cover: string implements Payload
{

	case OPEN = 'cover_open';

	case OPENING = 'cover_opening';

	case OPENED = 'cover_opened';

	case CLOSE = 'cover_close';

	case CLOSING = 'cover_closing';

	case CLOSED = 'cover_closed';

	case STOP = 'cover_stop';

	case STOPPED = 'cover_stopped';

	case CALIBRATE = 'cover_calibrate';

	case CALIBRATING = 'cover_calibrating';

	case LOCK = 'cover_lock';

}
