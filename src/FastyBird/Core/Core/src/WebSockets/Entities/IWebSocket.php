<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Entities;

use FastyBird\Core\WebSockets\Encoding;

interface IWebSocket
{

	public function setEstablished(bool $state): void;

	public function isEstablished(): bool;

	public function setClosing(bool $state): void;

	public function isClosing(): bool;

	public function getProtocol(): Encoding\IProtocol;

	public function setMessage(Encoding\IMessage $message): void;

	public function getMessage(): Encoding\IMessage;

	public function destroyMessage(): void;

	public function hasMessage(): bool;

	public function setFrame(Encoding\IFrame $frame): void;

	public function getFrame(): Encoding\IFrame;

	public function destroyFrame(): void;

	public function hasFrame(): bool;

}
