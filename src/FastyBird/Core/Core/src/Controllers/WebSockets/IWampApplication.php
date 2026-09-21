<?php declare(strict_types = 1);

/**
 * IApplication.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     Application
 * @since          1.0.0
 *
 * @date           16.02.17
 */

namespace FastyBird\Core\Controllers\WebSockets;

use FastyBird\Core\Entities\WebSockets\PushMessages;

/**
 * WebSockets WAMP application interface
 *
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     Application
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IWampApplication extends IApplication
{

	public function handlePush(PushMessages\IMessage $message, string $provider): void;

}
