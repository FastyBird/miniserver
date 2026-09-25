<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\WebSockets;

use Error;
use FastyBird\Core\WebSockets\Entities\WebSocket;
use FastyBird\Core\WebSockets\Encoding;
use FastyBird\Core\WebSockets\Entities;
use PHPUnit\Framework\TestCase;

/**
 * WebSocket::$message and $frame used to be typed properties with no default. RFC6455's
 * handleMessage() calls hasMessage() on every incoming frame -- including the very first one --
 * and destroyMessage()/destroyFrame() after every frame is consumed, before setMessage()/setFrame()
 * has ever run. Reading (hasMessage()) or assigning null to (destroyMessage()) an uninitialized/
 * non-nullable typed property both throw an Error, so every WebSocket connection died on its first
 * frame. These guard the exact sequence RFC6455 drives: hasMessage() must answer false before any
 * frame arrives, and destroyMessage()/destroyFrame() must not throw once a message/frame is set.
 */
final class WebSocketTest extends TestCase
{

	public function testHasMessageIsFalseBeforeTheFirstFrameAndTrueOnceSet(): void
	{
		$webSocket = new Entities\WebSocket(false, false, $this->createMock(Encoding\IProtocol::class));

		self::assertFalse($webSocket->hasMessage());

		$webSocket->setMessage($this->createMock(Encoding\IMessage::class));

		self::assertTrue($webSocket->hasMessage());
	}

	public function testDestroyMessageClearsAnAlreadySetMessageWithoutThrowing(): void
	{
		$webSocket = new Entities\WebSocket(false, false, $this->createMock(Encoding\IProtocol::class));
		$webSocket->setMessage($this->createMock(Encoding\IMessage::class));

		$webSocket->destroyMessage();

		self::assertFalse($webSocket->hasMessage());
	}

	public function testHasFrameIsFalseBeforeTheFirstFrameAndTrueOnceSet(): void
	{
		$webSocket = new Entities\WebSocket(false, false, $this->createMock(Encoding\IProtocol::class));

		self::assertFalse($webSocket->hasFrame());

		$webSocket->setFrame($this->createMock(Encoding\IFrame::class));

		self::assertTrue($webSocket->hasFrame());
	}

	public function testDestroyFrameClearsAnAlreadySetFrameWithoutThrowing(): void
	{
		$webSocket = new Entities\WebSocket(false, false, $this->createMock(Encoding\IProtocol::class));
		$webSocket->setFrame($this->createMock(Encoding\IFrame::class));

		$webSocket->destroyFrame();

		self::assertFalse($webSocket->hasFrame());
	}

	/**
	 * The RFC6455 handler's very first act on any incoming data is `$webSocket->hasMessage()`, with
	 * no setMessage() call anywhere before it on a freshly-opened connection.
	 */
	public function testHasMessageNeverThrowsOnAFreshConnection(): void
	{
		$webSocket = new Entities\WebSocket(false, false, $this->createMock(Encoding\IProtocol::class));

		try {
			$result = $webSocket->hasMessage();
		} catch (Error $error) {
			self::fail('hasMessage() threw on a fresh connection: ' . $error->getMessage());
		}

		self::assertFalse($result);
	}

}
