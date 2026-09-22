<?php declare(strict_types = 1);

namespace FastyBird\Core\Entities\WebSockets;

use FastyBird\Core\Encoding\WebSockets as Protocols;
use Nette;
use Override;
use TypeError;

final class WebSocket implements IWebSocket
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	private Protocols\IMessage $message;

	private Protocols\IFrame $frame;

	public function __construct(
		private bool $established,
		private bool $closing,
		private Protocols\IProtocol $protocol,
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
	public function getProtocol(): Protocols\IProtocol
	{
		return $this->protocol;
	}

	#[Override]
	public function setMessage(Protocols\IMessage $message): void
	{
		$this->message = $message;
	}

	#[Override]
	public function getMessage(): Protocols\IMessage
	{
		return $this->message;
	}

	/**
	 * @throws TypeError
	 */
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
	public function setFrame(Protocols\IFrame $frame): void
	{
		$this->frame = $frame;
	}

	#[Override]
	public function getFrame(): Protocols\IFrame
	{
		return $this->frame;
	}

	/**
	 * @throws TypeError
	 */
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
