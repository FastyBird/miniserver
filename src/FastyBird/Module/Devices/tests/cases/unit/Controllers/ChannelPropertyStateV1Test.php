<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Controllers;

use Error;
use FastyBird\Core\Constants;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Http;
use FastyBird\Core\Http\Routing;
use FastyBird\Module\Devices\Caching;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Tests;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use React\Http\Message\ServerRequest;
use RuntimeException;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class ChannelPropertyStateV1Test extends Tests\Cases\Unit\DbTestCase
{

	private const PROPERTY_ID = '28bc0d38-2f7c-4a71-aa74-27b102f8df4c';

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('channelPropertyStateRead')]
	public function testRead(string $url, string|null $token, int $lookups, int $statusCode, string $fixture): void
	{
		// The storage driver behind the module's state repository; production registers it through a bridge
		$stateRepository = $this->createMock(Models\States\Channels\IRepository::class);
		$stateRepository
			->expects(self::exactly($lookups))
			->method('find')
			->with(self::callback(
				static fn (Uuid\UuidInterface $id): bool => $id->toString() === self::PROPERTY_ID,
			))
			->willReturnCallback(
				static fn (Uuid\UuidInterface $id): Tests\Fixtures\Dummy\ChannelPropertyState => new Tests\Fixtures\Dummy\ChannelPropertyState(
					$id,
					'215',
					null,
					false,
					true,
				),
			);

		$this->mockContainerService(
			Models\States\Channels\Repository::class,
			new Models\States\Channels\Repository(
				$this->getContainer()->getByType(Caching\Container::class),
				$stateRepository,
			),
		);

		$router = $this->getContainer()->getByType(Routing\IRouter::class);

		$headers = [];

		if ($token !== null) {
			$headers['authorization'] = $token;
		}

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_GET,
			$url,
			$headers,
		);

		$response = $router->handle($request);

		self::assertTrue($response instanceof Http\Response);
		self::assertSame($statusCode, $response->getStatusCode());
		Tests\Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
		);
	}

	/**
	 * @return array<string, array<string|int|null>>
	 */
	public static function channelPropertyStateRead(): array
	{
		return [
			// Valid responses
			//////////////////
			'readDeviceChannel' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/channels/6821f8e9-ae69-4d5c-9b7c-d2b213f1ae0a/properties/' . self::PROPERTY_ID . '/state',
				'Bearer ' . self::VALID_TOKEN,
				1,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/channel.property.state.read.json',
			],
			'readChannel' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Constants::MODULE_DEVICES_PREFIX . '/v1/channels/6821f8e9-ae69-4d5c-9b7c-d2b213f1ae0a/properties/' . self::PROPERTY_ID . '/state',
				'Bearer ' . self::VALID_TOKEN,
				1,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/channel.property.state.read.channel.json',
			],

			// Invalid responses
			////////////////////
			'readMissingToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/channels/6821f8e9-ae69-4d5c-9b7c-d2b213f1ae0a/properties/' . self::PROPERTY_ID . '/state',
				null,
				0,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
		];
	}

}
