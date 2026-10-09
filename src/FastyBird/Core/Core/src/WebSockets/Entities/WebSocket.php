<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Entities;

use FastyBird\Core\WebSockets\Encoding;
use FastyBird\Core\WebSockets\Encoding\RFC6455;
use function assert;

final class WebSocket
{

	private RFC6455\Message|null $message = null;

	private RFC6455\Frame|null $frame = null;

	public function __construct(
		public bool $established,
		public bool $closing,
		private Encoding\RFC6455 $protocol,
	)
	{
	}

	public function getProtocol(): Encoding\RFC6455
	{
		return $this->protocol;
	}

	public function setMessage(RFC6455\Message $message): void
	{
		$this->message = $message;
	}

	public function getMessage(): RFC6455\Message
	{
		assert($this->message !== null);

		return $this->message;
	}

	public function destroyMessage(): void
	{
		$this->message = null;
	}

	public function hasMessage(): bool
	{
		return $this->message !== null;
	}

	public function setFrame(RFC6455\Frame $frame): void
	{
		$this->frame = $frame;
	}

	public function getFrame(): RFC6455\Frame
	{
		assert($this->frame !== null);

		return $this->frame;
	}

	public function destroyFrame(): void
	{
		$this->frame = null;
	}

	public function hasFrame(): bool
	{
		return $this->frame !== null;
	}

}
