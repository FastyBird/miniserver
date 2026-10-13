<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Tests\Support;

use Evenement\EventEmitter;
use Override;
use React\Socket;
use React\Stream;

/**
 * Stream connection driven by the test: it emits what the test tells it to and records what was written to it
 *
 * Nothing touches the network, so a test raises `data`, `error` and `close` exactly when it wants to.
 */
final class FakeConnection extends EventEmitter implements Socket\ConnectionInterface
{

	/** @var array<string> */
	private array $written = [];

	private bool $open = true;

	/**
	 * @return array<string>
	 */
	public function getWritten(): array
	{
		return $this->written;
	}

	#[Override]
	public function isReadable(): bool
	{
		return $this->open;
	}

	#[Override]
	public function isWritable(): bool
	{
		return $this->open;
	}

	#[Override]
	public function pause(): void
	{
		// Nothing to pause
	}

	#[Override]
	public function resume(): void
	{
		// Nothing to resume
	}

	/**
	 * @param array<string, mixed> $options
	 */
	#[Override]
	public function pipe(Stream\WritableStreamInterface $dest, array $options = []): Stream\WritableStreamInterface
	{
		return $dest;
	}

	#[Override]
	public function write($data): bool
	{
		$this->written[] = (string) $data;

		return true;
	}

	#[Override]
	public function end($data = null): void
	{
		if ($data !== null) {
			$this->write($data);
		}

		$this->close();
	}

	#[Override]
	public function close(): void
	{
		if (!$this->open) {
			return;
		}

		$this->open = false;

		$this->emit('close');
	}

	#[Override]
	public function getRemoteAddress(): string|null
	{
		return 'tcp://127.0.0.1:1';
	}

	#[Override]
	public function getLocalAddress(): string|null
	{
		return 'tcp://127.0.0.1:2';
	}

}
