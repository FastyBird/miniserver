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

namespace FastyBird\Library\WebSockets\Wamp\Application;

use FastyBird\Library\WebSockets\Application as WebSocketsApplication;
use FastyBird\Library\WebSockets\Wamp\Entities;

/**
 * WebSockets WAMP application interface
 *
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     Application
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IApplication extends WebSocketsApplication\IApplication
{

	public function handlePush(Entities\PushMessages\IMessage $message, string $provider): void;

}
