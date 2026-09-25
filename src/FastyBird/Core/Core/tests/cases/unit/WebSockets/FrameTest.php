<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\WebSockets;

use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\WebSockets\Encoding;
use FastyBird\Core\WebSockets\Encoding\RFC6455;
use FastyBird\Core\WebSockets\Entities\Topics as EntitiesTopics;
use FastyBird\Core\WebSockets\Exceptions as WebSocketsExceptions;
use FastyBird\Core\WebSockets\Topics as WebSocketsTopics;
use FastyBird\Core\WebSockets\Topics\Drivers;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use UnderflowException;
use function intdiv;
use function ord;
use function str_repeat;
use function strlen;
use function substr;

/**
 * Characterizes the RFC6455 framing/masking behaviour and the in-memory topic storage as they
 * exist today, ahead of the E3 move to `FastyBird\Core\WebSockets\`. This is the largest Core
 * capability (84 files) and the one with no existing coverage.
 */
final class FrameTest extends TestCase
{

	/**
	 * @throws UnderflowException
	 */
	public function testConstructorSetsFinalPayloadLengthAndCoalescedFlag(): void
	{
		$frame = new RFC6455\Frame('Hello', true, RFC6455\Frame::OP_TEXT);

		self::assertTrue($frame->isFinal());
		self::assertSame('Hello', $frame->getPayload());
		self::assertSame(5, $frame->getPayloadLength());
		self::assertTrue($frame->isCoalesced());
	}

	/**
	 * A masked frame's raw wire bytes differ from the plaintext payload while masked, and
	 * getPayload() transparently demasks them. unMaskPayload() must restore the exact original
	 * bytes, not merely an equal-looking payload.
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws OutOfBoundsException
	 * @throws UnderflowException
	 */
	public function testMaskPayloadThenUnMaskPayloadRoundTripsAndMasksIntermediateBytes(): void
	{
		$frame = new RFC6455\Frame('Hello', true, RFC6455\Frame::OP_TEXT);
		$originalContents = $frame->getContents();

		$frame->maskPayload('abcd');

		self::assertTrue($frame->isMasked());

		$rawMaskedPayload = substr($frame->getContents(), $frame->getPayloadStartingByte());

		self::assertNotSame('Hello', $rawMaskedPayload);
		self::assertSame('Hello', $frame->getPayload());

		$frame->unMaskPayload();

		self::assertFalse($frame->isMasked());
		self::assertSame('Hello', $frame->getPayload());
		self::assertSame($originalContents, $frame->getContents());
	}

	/**
	 * @throws UnderflowException
	 */
	public function testGetContentsOfUnmaskedTextFrameStartsWithFinBitAndTextOpCode(): void
	{
		$frame = new RFC6455\Frame('Hello', true, RFC6455\Frame::OP_TEXT);

		self::assertSame(0x81, ord(substr($frame->getContents(), 0, 1)));
	}

	/**
	 * @throws UnderflowException
	 */
	public function testAddBufferInTwoChunksCoalescesOnlyOnceComplete(): void
	{
		$source = new RFC6455\Frame('Chunked', true, RFC6455\Frame::OP_TEXT);
		$contents = $source->getContents();
		$midPoint = intdiv(strlen($contents), 2);

		$rebuilt = new RFC6455\Frame();
		$rebuilt->addBuffer(substr($contents, 0, $midPoint));

		self::assertFalse($rebuilt->isCoalesced());

		$rebuilt->addBuffer(substr($contents, $midPoint));

		self::assertTrue($rebuilt->isCoalesced());
		self::assertSame('Chunked', $rebuilt->getPayload());
	}

	/**
	 * A payload of 126-65535 bytes switches the length marker (the frame's second byte, masked
	 * off) from an inline 7-bit value to the sentinel 126 plus a 16-bit big-endian extension. The
	 * marker bytes themselves are only read back when a *different* frame parses raw wire bytes
	 * via addBuffer() -- getPayloadLength() on the originating frame returns its cached in-memory
	 * length regardless of what landed in the extension bytes. So this round-trips through
	 * getContents() into a freshly parsed frame, the way a real receiver would, to actually
	 * exercise the 16-bit marker rather than merely the cached constructor value.
	 *
	 * @throws UnderflowException
	 */
	public function testExtendedLength16BitMarkerRoundTripsThroughContentsAndAddBuffer(): void
	{
		$payload = str_repeat('A', 200);

		$source = new RFC6455\Frame($payload, true, RFC6455\Frame::OP_TEXT);

		self::assertSame(200, $source->getPayloadLength());
		self::assertSame(4, $source->getPayloadStartingByte());

		$contents = $source->getContents();

		self::assertSame(126, ord(substr($contents, 1, 1)));

		$rebuilt = new RFC6455\Frame();
		$rebuilt->addBuffer($contents);

		self::assertSame(200, $rebuilt->getPayloadLength());
		self::assertSame(4, $rebuilt->getPayloadStartingByte());
		self::assertSame($payload, $rebuilt->getPayload());
	}

	/**
	 * A payload above 65535 bytes switches the length marker to the sentinel 127 plus a 64-bit
	 * big-endian extension (implemented as two 32-bit halves, the high half always zero). Same
	 * reasoning as the 16-bit case: only a freshly parsed frame actually reads those extension
	 * bytes back.
	 *
	 * @throws UnderflowException
	 */
	public function testExtendedLength64BitMarkerRoundTripsThroughContentsAndAddBuffer(): void
	{
		$payload = str_repeat('B', 70_000);

		$source = new RFC6455\Frame($payload, true, RFC6455\Frame::OP_TEXT);

		self::assertSame(70_000, $source->getPayloadLength());
		self::assertSame(10, $source->getPayloadStartingByte());

		$contents = $source->getContents();

		self::assertSame(127, ord(substr($contents, 1, 1)));

		$rebuilt = new RFC6455\Frame();
		$rebuilt->addBuffer($contents);

		self::assertSame(70_000, $rebuilt->getPayloadLength());
		self::assertSame(10, $rebuilt->getPayloadStartingByte());
		self::assertSame($payload, $rebuilt->getPayload());
	}

	/**
	 * @throws UnderflowException
	 */
	public function testExtractOverflowReturnsBytesBeyondFirstFrameAndLeavesItIntact(): void
	{
		$frameA = new RFC6455\Frame('First', true, RFC6455\Frame::OP_TEXT);
		$frameB = new RFC6455\Frame('Second', true, RFC6455\Frame::OP_TEXT);

		$combined = new RFC6455\Frame();
		$combined->addBuffer($frameA->getContents() . $frameB->getContents());

		$overflow = $combined->extractOverflow();

		self::assertNotSame('', $overflow);
		self::assertSame('First', $combined->getPayload());

		$overflowFrame = new RFC6455\Frame();
		$overflowFrame->addBuffer($overflow);

		self::assertSame('Second', $overflowFrame->getPayload());
	}

	/**
	 * @throws UnderflowException
	 */
	public function testExtractOverflowReturnsEmptyStringWhenThereIsNoOverflow(): void
	{
		$single = new RFC6455\Frame('Solo', true, RFC6455\Frame::OP_TEXT);

		$frame = new RFC6455\Frame();
		$frame->addBuffer($single->getContents());

		self::assertSame('', $frame->extractOverflow());
	}

	/**
	 * Message does not validate opcode sequencing: two OP_CONTINUE frames concatenate fine even
	 * though the first is not final and RFC6455 would never open a message with a continuation
	 * opcode.
	 *
	 * @throws UnderflowException
	 */
	public function testMessageAddFrameTwiceConcatenatesPayloadAndCountsFrames(): void
	{
		$message = new RFC6455\Message();

		self::assertCount(0, $message);

		$first = new RFC6455\Frame('Hello ', false, RFC6455\Frame::OP_CONTINUE);
		$message->addFrame($first);

		self::assertCount(1, $message);
		self::assertFalse($message->isCoalesced());

		$second = new RFC6455\Frame('World', true, RFC6455\Frame::OP_CONTINUE);
		$message->addFrame($second);

		self::assertCount(2, $message);
		self::assertTrue($message->isCoalesced());
		self::assertSame('Hello World', $message->getPayload());
	}

	/**
	 * `ext-mbstring` is a hard requirement in both the root and Core `composer.json`, so
	 * `extension_loaded('mbstring')` inside `isUtf8()` is always true and `mb_check_encoding()`
	 * always runs first. Every rejection this assertion observes is decided there -- the
	 * hand-rolled DFA loop underneath only ever sees strings mbstring has already accepted, so
	 * its `UTF8_REJECT` branch is unreachable while that requirement holds. That loop is dead
	 * code as written, flagged for removal in the API-rewrite epic rather than exercised here:
	 * this test covers the accept path only, not the DFA's own rejection logic.
	 */
	public function testValidatorCheckEncodingAcceptsValidUtf8AndRejectsInvalidByteSequence(): void
	{
		$validator = new Encoding\Validator();

		self::assertTrue($validator->checkEncoding('valid utf8', 'UTF-8'));
		self::assertFalse($validator->checkEncoding("\xC3\x28", 'UTF-8'));
	}

	public function testInMemoryDriverSaveFetchContainsDeleteAndFetchAllRoundTrip(): void
	{
		$driver = new Drivers\InMemory();
		$topic = new EntitiesTopics\Topic('topic-1');

		self::assertFalse($driver->contains('topic-1'));

		self::assertTrue($driver->save('topic-1', $topic, 0));

		self::assertTrue($driver->contains('topic-1'));
		self::assertSame($topic, $driver->fetch('topic-1'));
		self::assertSame([$topic], $driver->fetchAll());

		self::assertTrue($driver->delete('topic-1'));

		self::assertFalse($driver->contains('topic-1'));
		self::assertFalse($driver->fetch('topic-1'));
		self::assertSame([], $driver->fetchAll());
	}

	/**
	 * @throws WebSocketsExceptions\Storage
	 * @throws WebSocketsExceptions\TopicNotFound
	 */
	public function testTopicsStorageAddHasGetRemoveAndIterate(): void
	{
		$storage = new WebSocketsTopics\Storage();
		$storage->setStorageDriver(new Drivers\InMemory());

		$topicA = new EntitiesTopics\Topic('topic-a');
		$topicB = new EntitiesTopics\Topic('topic-b');

		$storage->addTopic('topic-a', $topicA);
		$storage->addTopic('topic-b', $topicB);

		self::assertTrue($storage->hasTopic('topic-a'));
		self::assertSame($topicA, $storage->getTopic('topic-a'));

		self::assertTrue($storage->removeTopic('topic-a'));
		self::assertFalse($storage->hasTopic('topic-a'));

		$ids = [];

		foreach ($storage->getIterator() as $topic) {
			self::assertInstanceOf(EntitiesTopics\Topic::class, $topic);

			$ids[] = $topic->getId();
		}

		self::assertSame(['topic-b'], $ids);
	}

}
