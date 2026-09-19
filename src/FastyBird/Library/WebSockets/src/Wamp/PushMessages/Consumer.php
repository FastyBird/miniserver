<?php declare(strict_types = 1);

/**
 * Consumer.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     PushMessages
 * @since          1.0.0
 *
 * @date           28.02.17
 */

namespace FastyBird\Library\WebSockets\Wamp\PushMessages;

use Nette;

/**
 * Server push consumer
 *
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     PushMessages
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
abstract class Consumer implements IConsumer
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	public function __construct(private string $name)
	{
	}

	public function getName(): string
	{
		return $this->name;
	}

}
