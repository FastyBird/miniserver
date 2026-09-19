<?php declare(strict_types = 1);

namespace FastyBird\Library\WebSockets\Entities\WebSockets;

use FastyBird\Library\WebSockets\Protocols;

interface IWebSocket
{

	public function setEstablished(bool $state): void;

	public function isEstablished(): bool;

	public function setClosing(bool $state): void;

	public function isClosing(): bool;

	public function getProtocol(): Protocols\IProtocol;

	public function setMessage(Protocols\IMessage $message): void;

	public function getMessage(): Protocols\IMessage;

	public function destroyMessage(): void;

	public function hasMessage(): bool;

	public function setFrame(Protocols\IFrame $frame): void;

	public function getFrame(): Protocols\IFrame;

	public function destroyFrame(): void;

	public function hasFrame(): bool;

}
