<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\WebSockets;

use FastyBird\Core\Encoding\WebSockets\RFC6455\Frame;
use FastyBird\Core\Encoding\WebSockets\RFC6455\Message;
use FastyBird\Core\Encoding\WebSockets\Validator;
use FastyBird\Core\Entities\WsServer\Topics\Topic;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Topics\WsServer\Drivers\InMemory;
use FastyBird\Core\Topics\WsServer\Storage;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use UnderflowException;
use function intdiv;
use function ord;
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
		$frame = new Frame('Hello', true, Frame::OP_TEXT);

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
	 * @throws Exceptions\InvalidArgument
	 * @throws OutOfBoundsException
	 * @throws UnderflowException
	 */
	public function testMaskPayloadThenUnMaskPayloadRoundTripsAndMasksIntermediateBytes(): void
	{
		$frame = new Frame('Hello', true, Frame::OP_TEXT);
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
		$frame = new Frame('Hello', true, Frame::OP_TEXT);

		self::assertSame(0x81, ord(substr($frame->getContents(), 0, 1)));
	}

	/**
	 * @throws UnderflowException
	 */
	public function testAddBufferInTwoChunksCoalescesOnlyOnceComplete(): void
	{
		$source = new Frame('Chunked', true, Frame::OP_TEXT);
		$contents = $source->getContents();
		$midPoint = intdiv(strlen($contents), 2);

		$rebuilt = new Frame();
		$rebuilt->addBuffer(substr($contents, 0, $midPoint));

		self::assertFalse($rebuilt->isCoalesced());

		$rebuilt->addBuffer(substr($contents, $midPoint));

		self::assertTrue($rebuilt->isCoalesced());
		self::assertSame('Chunked', $rebuilt->getPayload());
	}

	/**
	 * @throws UnderflowException
	 */
	public function testExtractOverflowReturnsBytesBeyondFirstFrameAndLeavesItIntact(): void
	{
		$frameA = new Frame('First', true, Frame::OP_TEXT);
		$frameB = new Frame('Second', true, Frame::OP_TEXT);

		$combined = new Frame();
		$combined->addBuffer($frameA->getContents() . $frameB->getContents());

		$overflow = $combined->extractOverflow();

		self::assertNotSame('', $overflow);
		self::assertSame('First', $combined->getPayload());

		$overflowFrame = new Frame();
		$overflowFrame->addBuffer($overflow);

		self::assertSame('Second', $overflowFrame->getPayload());
	}

	/**
	 * @throws UnderflowException
	 */
	public function testExtractOverflowReturnsEmptyStringWhenThereIsNoOverflow(): void
	{
		$single = new Frame('Solo', true, Frame::OP_TEXT);

		$frame = new Frame();
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
		$message = new Message();

		self::assertCount(0, $message);

		$first = new Frame('Hello ', false, Frame::OP_CONTINUE);
		$message->addFrame($first);

		self::assertCount(1, $message);
		self::assertFalse($message->isCoalesced());

		$second = new Frame('World', true, Frame::OP_CONTINUE);
		$message->addFrame($second);

		self::assertCount(2, $message);
		self::assertTrue($message->isCoalesced());
		self::assertSame('Hello World', $message->getPayload());
	}

	public function testValidatorCheckEncodingAcceptsValidUtf8AndRejectsInvalidByteSequence(): void
	{
		$validator = new Validator();

		self::assertTrue($validator->checkEncoding('valid utf8', 'UTF-8'));
		self::assertFalse($validator->checkEncoding("\xC3\x28", 'UTF-8'));
	}

	public function testInMemoryDriverSaveFetchContainsDeleteAndFetchAllRoundTrip(): void
	{
		$driver = new InMemory();
		$topic = new Topic('topic-1');

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
	 * @throws Exceptions\Storage
	 * @throws Exceptions\TopicNotFound
	 */
	public function testTopicsStorageAddHasGetRemoveAndIterate(): void
	{
		$storage = new Storage();
		$storage->setStorageDriver(new InMemory());

		$topicA = new Topic('topic-a');
		$topicB = new Topic('topic-b');

		$storage->addTopic('topic-a', $topicA);
		$storage->addTopic('topic-b', $topicB);

		self::assertTrue($storage->hasTopic('topic-a'));
		self::assertSame($topicA, $storage->getTopic('topic-a'));

		self::assertTrue($storage->removeTopic('topic-a'));
		self::assertFalse($storage->hasTopic('topic-a'));

		$ids = [];

		foreach ($storage->getIterator() as $topic) {
			self::assertInstanceOf(Topic::class, $topic);

			$ids[] = $topic->getId();
		}

		self::assertSame(['topic-b'], $ids);
	}

}
