<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Dummy;

use React\Socket;
use React\Stream;

/**
 * A no-op socket connection double. Exposes the dynamic $stream property Handlers reads to
 * identify a client and an $ended flag so a test can assert end() was actually called, neither
 * of which a plain PHPUnit interface mock can offer without triggering an "undefined property"
 * warning under a fixed ConnectionInterface type.
 */
final class DummyWsConnection implements Socket\ConnectionInterface
{

	public int $stream = 1;

	public bool $ended = false;

	public function getRemoteAddress(): string|null
	{
		return null;
	}

	public function getLocalAddress(): string|null
	{
		return null;
	}

	public function isReadable(): bool
	{
		return true;
	}

	public function pause(): void
	{
		// Not exercised by the tests using this double
	}

	public function resume(): void
	{
		// Not exercised by the tests using this double
	}

	/**
	 * @param array<string, mixed> $options
	 */
	public function pipe(Stream\WritableStreamInterface $dest, array $options = []): Stream\WritableStreamInterface
	{
		return $dest;
	}

	public function close(): void
	{
		// Not exercised by the tests using this double
	}

	public function isWritable(): bool
	{
		return true;
	}

	public function write(mixed $data): bool
	{
		return true;
	}

	public function end(mixed $data = null): void
	{
		$this->ended = true;
	}

	/**
	 * Evenement\EventEmitterInterface declares $event and $listener with no native type, so a
	 * native `string` type here would narrow the parameter and be a fatal LSP violation, not
	 * just a PHPStan complaint. `mixed` is the widest native type PHP has, so it is exactly as
	 * permissive as no type at all and satisfies both the engine and the coding standard.
	 *
	 * @param callable(mixed ...$args): void $listener
	 */
	public function on(mixed $event, callable $listener): void
	{
		// Not exercised by the tests using this double -- handleError() is invoked directly
	}

	/**
	 * @param callable(mixed ...$args): void $listener
	 */
	public function once(mixed $event, callable $listener): void
	{
		// Not exercised by the tests using this double -- handleError() is invoked directly
	}

	/**
	 * @param callable(mixed ...$args): void $listener
	 */
	public function removeListener(mixed $event, callable $listener): void
	{
		// Not exercised by the tests using this double -- handleError() is invoked directly
	}

	public function removeAllListeners(mixed $event = null): void
	{
		// Not exercised by the tests using this double -- handleError() is invoked directly
	}

	/**
	 * @return array<callable>
	 */
	public function listeners(mixed $event = null): array
	{
		return [];
	}

	/**
	 * @param array<mixed> $arguments
	 */
	public function emit(mixed $event, array $arguments = []): void
	{
		// Not exercised by the tests using this double -- handleError() is invoked directly
	}

}
