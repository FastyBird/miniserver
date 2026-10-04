<?php declare(strict_types = 1);

namespace FastyBird\MiniServer\Tests\Cases\Application;

use Error;
use JsonException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Characterization, at production scope, of what happens when the WebSocket server is created,
 * started and stopped (#460 §1.3, §3.4).
 *
 * Today the order is fixed by the sequence of addSetup() calls on ServerRuntime: Core's
 * WebSocketsExtension bridges onCreate/onStart/onStop to the event dispatcher and appends the
 * WAMP onServerStart subscriber to onStart; then Devices, Ui and DevicesModuleUiModule -- in
 * config/common.neon's extension order -- each append an onCreate closure that ENABLES their
 * SocketsBridge exchange consumer, which is registered disabled. That is what makes exchange
 * messages reach WAMP clients only once the server exists. E5.6 (#638) turns all of it into
 * PSR-14 listeners whose priorities must reproduce exactly this; changing when a SocketsBridge
 * is enabled is an escalation (#634).
 *
 * A listener at the highest priority records each event as it is dispatched, together with the
 * bridges enabled at that moment. The bridges are listed in the exchange consumer container's
 * order, which is the order they were enabled in.
 */
final class WebSocketsServerLifecycleTest extends TestCase
{

	use ProductionProbe;

	private const string ALL_BRIDGES = '[Devices, Ui, DevicesModuleUiModule]';

	/**
	 * @throws Error
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testCreateStartAndStopDispatchInOrderAndCreateEnablesTheModuleBridges(): void
	{
		self::assertSame(
			[
				'before create []',
				'dispatched CreateEvent []',
				'after create ' . self::ALL_BRIDGES,
				'dispatched StartEvent ' . self::ALL_BRIDGES,
				'loop running',
				'after run',
				'dispatched StopEvent ' . self::ALL_BRIDGES,
				'after stop',
			],
			$this->probe('server-lifecycle'),
		);
	}

	/**
	 * The WAMP onServerStart subscriber is the second start hook: it connects the push consumers
	 * after the start event has been dispatched and before the event loop runs.
	 *
	 * @throws Error
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testThePushConsumersConnectAfterTheStartEventAndBeforeTheLoopRuns(): void
	{
		self::assertSame(
			[
				'before create []',
				'dispatched CreateEvent []',
				'after create ' . self::ALL_BRIDGES,
				'dispatched StartEvent ' . self::ALL_BRIDGES,
				'push consumer connected',
				'loop running',
				'after run',
				'dispatched StopEvent ' . self::ALL_BRIDGES,
				'after stop',
			],
			$this->probe('server-push-consumers'),
		);
	}

}
