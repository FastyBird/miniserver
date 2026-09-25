<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Entities;

use FastyBird\Core\WebSockets\Encoding;
use Override;
use function assert;

final class WebSocket implements IWebSocket
{

	private Encoding\IMessage|null $message = null;

	private Encoding\IFrame|null $frame = null;

	public function __construct(
		private bool $established,
		private bool $closing,
		private Encoding\IProtocol $protocol,
	)
	{
	}

	#[Override]
	public function setEstablished(bool $state): void
	{
		$this->established = $state;
	}

	#[Override]
	public function isEstablished(): bool
	{
		return $this->established;
	}

	#[Override]
	public function setClosing(bool $state): void
	{
		$this->closing = $state;
	}

	#[Override]
	public function isClosing(): bool
	{
		return $this->closing;
	}

	#[Override]
	public function getProtocol(): Encoding\IProtocol
	{
		return $this->protocol;
	}

	#[Override]
	public function setMessage(Encoding\IMessage $message): void
	{
		$this->message = $message;
	}

	#[Override]
	public function getMessage(): Encoding\IMessage
	{
		assert($this->message !== null);

		return $this->message;
	}

	#[Override]
	public function destroyMessage(): void
	{
		$this->message = null;
	}

	#[Override]
	public function hasMessage(): bool
	{
		return $this->message !== null;
	}

	#[Override]
	public function setFrame(Encoding\IFrame $frame): void
	{
		$this->frame = $frame;
	}

	#[Override]
	public function getFrame(): Encoding\IFrame
	{
		assert($this->frame !== null);

		return $this->frame;
	}

	#[Override]
	public function destroyFrame(): void
	{
		$this->frame = null;
	}

	#[Override]
	public function hasFrame(): bool
	{
		return $this->frame !== null;
	}

}
