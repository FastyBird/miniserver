<?php declare(strict_types = 1);

/**
 * Constants.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     common
 * @since          0.2.0
 *
 * @date           15.01.22
 */

namespace FastyBird\MiniServer;

/**
 * Service constants
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     common
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Constants
{

	/**
	 * Service headers
	 */

	public const WS_HEADER_AUTHORIZATION = 'authorization';
	public const WS_HEADER_WS_KEY = 'x-ws-key';
	public const WS_HEADER_ORIGIN = 'origin';

}
