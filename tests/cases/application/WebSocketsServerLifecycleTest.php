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
 * ServerRuntime dispatches ServerCreated, ServerStarted and ServerStopped itself (#638). Devices,
 * Ui and DevicesModuleUiModule each register a ServerCreated listener through
 * WebSocketsExtension::SERVER_CREATED_LISTENER_TAG, at -10, -20 and -30 (#658), that ENABLES their
 * SocketsBridge exchange consumer, which is registered disabled. That is what makes exchange
 * messages reach WAMP clients only once the server exists. Before #638 the same order came from
 * the sequence of addSetup() calls on ServerRuntime; changing when a SocketsBridge is enabled is
 * an escalation (#634).
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
				'dispatched ServerCreated []',
				'after create ' . self::ALL_BRIDGES,
				'dispatched ServerStarted ' . self::ALL_BRIDGES,
				'loop running',
				'after run',
				'dispatched ServerStopped ' . self::ALL_BRIDGES,
				'after stop',
			],
			$this->probe('server-lifecycle'),
		);
	}

}
