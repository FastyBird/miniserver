<?php declare(strict_types = 1);

/**
 * Message.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           28.02.17
 */

namespace FastyBird\Library\WebSockets\Wamp\Entities\PushMessages;

use Nette;

/**
 * A push message
 *
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class Message implements IMessage
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	public function __construct(private string $topic, private array $data)
	{
	}

	public function getTopic(): string
	{
		return $this->topic;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getData(): array
	{
		return $this->data;
	}

	public function jsonSerialize(): array
	{
		return [
			'topic' => $this->topic,
			'data' => $this->data,
		];
	}

}
