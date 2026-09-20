<?php declare(strict_types = 1);

/**
 * Constants.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     common
 * @since          1.0.0
 *
 * @date           09.03.20
 */

namespace FastyBird\Core\Constants\WsServer;

/**
 * Service constants
 *
 * @package        FastyBird:WsServerPlugin!
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
