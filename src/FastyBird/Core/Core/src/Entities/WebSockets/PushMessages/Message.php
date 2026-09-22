<?php declare(strict_types = 1);

namespace FastyBird\Core\Entities\WebSockets\PushMessages;

use Nette;
use Override;

/**
 * A push message
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

	#[Override]
	public function getTopic(): string
	{
		return $this->topic;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
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
