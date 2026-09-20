<?php declare(strict_types = 1);

/**
 * Button.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           17.11.21
 */

namespace FastyBird\Core\Types\Metadata\Payloads;

/**
 * Button supported payload types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum Button: string implements Payload
{

	case PRESSED = 'btn_pressed';

	case RELEASED = 'btn_released';

	case CLICKED = 'btn_clicked';

	case DOUBLE_CLICKED = 'btn_double_clicked';

	case TRIPLE_CLICKED = 'btn_triple_clicked';

	case LONG_CLICKED = 'btn_long_clicked';

	case EXTRA_LONG_CLICKED = 'btn_extra_long_clicked';

}
