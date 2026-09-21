<?php declare(strict_types = 1);

namespace FastyBird\Core\Entities\WebSockets;

use FastyBird\Core\Encoding\WebSockets as Protocols;
use Nette;

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

	public function setEstablished(bool $state): void
	{
		$this->established = $state;
	}

	public function isEstablished(): bool
	{
		return $this->established;
	}

	public function setClosing(bool $state): void
	{
		$this->closing = $state;
	}

	public function isClosing(): bool
	{
		return $this->closing;
	}

	public function getProtocol(): Protocols\IProtocol
	{
		return $this->protocol;
	}

	public function setMessage(Protocols\IMessage $message): void
	{
		$this->message = $message;
	}

	public function getMessage(): Protocols\IMessage
	{
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

	public function setFrame(Protocols\IFrame $frame): void
	{
		$this->frame = $frame;
	}

	public function getFrame(): Protocols\IFrame
	{
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
