<?php declare(strict_types = 1);

namespace FastyBird\MiniServer\Tests\Cases\Application;

use Error;
use JsonException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * At production scope, the Devices and Ui SocketsBridge consumers broadcast an exchange document
 * to the clients subscribed to their own module's WAMP exchange topic, and to no other (#625).
 *
 * Each client receives the WAMP EVENT frame [8, topic, message], where the message is the JSON
 * the bridge built from the routing key, the source and the document. The DevicesModuleUiModule
 * bridge needs the database for its widget data sources; its test is in that package.
 */
final class SocketsBridgeBroadcastTest extends TestCase
{

	use ProductionProbe;

	/**
	 * @throws Error
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testEachModuleBridgeBroadcastsToItsOwnExchangeTopic(): void
	{
		self::assertSame(
			[
				'Devices' => [
					'/devices-module/v1/exchange' => [
						[
							'type' => 8,
							'topic' => '/devices-module/v1/exchange',
							'message' => [
								'routing_key' => 'fb.exchange.module.document.reported.channel.property.state',
								'source' => 'com.fastybird.virtual-connector',
								'data' => [
									'id' => '28bc0d38-2f7c-4a71-aa74-27b102f8df4c',
									'read' => ['actual_value' => 21.5, 'expected_value' => null],
									'get' => ['actual_value' => null, 'expected_value' => null],
									'pending' => false,
									'valid' => true,
									'created_at' => null,
									'updated_at' => null,
									'channel' => '6821f8e9-ae69-4d5c-9b7c-d2b213f1ae0a',
								],
							],
						],
					],
					'/ui-module/v1/exchange' => [],
				],
				'Ui' => [
					'/devices-module/v1/exchange' => [],
					'/ui-module/v1/exchange' => [
						[
							'type' => 8,
							'topic' => '/ui-module/v1/exchange',
							'message' => [
								'routing_key' => 'fb.exchange.module.document.reported.group',
								'source' => 'com.fastybird.ui-module',
								'data' => [
									'id' => '89f4a14f-7f78-4216-99b8-584ab9229f1c',
									'source' => 'com.fastybird.ui-module',
									'identifier' => 'living-room',
									'name' => 'Living room',
									'comment' => null,
									'priority' => 0,
									'widgets' => [],
									'owner' => null,
									'created_at' => null,
									'updated_at' => null,
								],
							],
						],
					],
				],
			],
			$this->probe('sockets-bridges'),
		);
	}

}
