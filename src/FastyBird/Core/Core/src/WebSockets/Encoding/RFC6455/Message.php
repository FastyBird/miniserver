<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Encoding\RFC6455;

use Countable;
use FastyBird\Core\WebSockets\Encoding;
use Override;
use SplDoublyLinkedList;
use UnderflowException;
use function count;

/**
 * Communication message
 */
final class Message implements Encoding\IMessage, Countable
{

	private SplDoublyLinkedList $frames;

	public function __construct()
	{
		$this->frames = new SplDoublyLinkedList();
	}

	#[Override]
	public function count(): int
	{
		return count($this->frames);
	}

	#[Override]
	public function isCoalesced(): bool
	{
		if (count($this->frames) === 0) {
			return false;
		}

		$last = $this->frames->top();

		return $last->isCoalesced() && $last->isFinal();
	}

	/**
	 * @todo Also, I should perhaps check the type...control frames (ping/pong/close) are not to be considered part of a message
	 *
	 * {@inheritDoc}
	 */
	#[Override]
	public function addFrame(Encoding\IFrame $fragment): void
	{
		$this->frames->push($fragment);
	}

	/**
	 * @throws UnderflowException
	 */
	#[Override]
	public function getOpCode(): int
	{
		if (count($this->frames) === 0) {
			throw new UnderflowException('No frames have been added to this message');
		}

		return $this->frames->bottom()->getOpCode();
	}

	#[Override]
	public function getPayloadLength(): int
	{
		$len = 0;

		foreach ($this->frames as $frame) {
			try {
				$len += $frame->getPayloadLength();

			} catch (UnderflowException) {
				// Not an error, want the current amount buffered
			}
		}

		return $len;
	}

	/**
	 * @throws UnderflowException
	 */
	#[Override]
	public function getPayload(): string
	{
		if (!$this->isCoalesced()) {
			throw new UnderflowException('Message has not been put back together yet');
		}

		$buffer = '';

		foreach ($this->frames as $frame) {
			$buffer .= $frame->getPayload();
		}

		return $buffer;
	}

	/**
	 * @throws UnderflowException
	 */
	#[Override]
	public function getContents(): string
	{
		if (!$this->isCoalesced()) {
			throw new UnderflowException('Message has not been put back together yet');
		}

		$buffer = '';

		foreach ($this->frames as $frame) {
			$buffer .= $frame->getContents();
		}

		return $buffer;
	}

}
