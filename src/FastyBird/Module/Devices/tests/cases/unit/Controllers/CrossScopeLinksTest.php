<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Controllers;

use Error;
use FastyBird\Core\Constants;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Http;
use FastyBird\Core\Http\Routing;
use FastyBird\Module\Devices\Tests;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use React\Http\Message\ServerRequest;
use RuntimeException;
use function is_array;
use function is_string;
use function sprintf;

/**
 * A link in a response from a scoped route must resolve, also when it points at a resource in
 * another device or connector (#627, #628).
 *
 * The removed Devices UrlFormat middleware rewrote every link in such a response into the
 * request's scope, so these links answered 404: a mapped property's parent on another device or
 * connector, and a connector-scoped device's channels, parents and children, which have no
 * connector-scoped route at all.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class CrossScopeLinksTest extends Tests\Cases\Unit\DbTestCase
{

	private const string PREFIX = '/api/' . Constants::MODULE_DEVICES_PREFIX . '/v1';

	private const string GENERIC_CONNECTOR = '17c59dfa-2edd-438e-8c49-faa4e38e5a5e';

	private const string DUMMY_CONNECTOR = '7a3dd94c-7294-46fd-8c61-1b375c313d4d';

	private const string FIRST_DEVICE = '69786d15-fd0c-4d9f-9378-33287c2009fa';

	private const string SECOND_DEVICE = 'bf4cd870-2aac-45f0-a85e-e1cefd2d6d9a';

	private const string FOREIGN_DEVICE = '4f9a1c2e-7b3d-4e8f-a6c5-1d2e3f4a5b6c';

	public function setUp(): void
	{
		// A device on the dummy connector with a mapped property whose parent is on the first
		// device, on the generic connector; and a mapped channel property on the second device
		// whose parent is on a channel of the first device.
		$this->registerDatabaseSchemaFile(__DIR__ . '/../../../sql/cross.scope.links.sql');

		parent::setUp();
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	public function testMappedDevicePropertyParentOnAnotherDeviceAndConnector(): void
	{
		$document = $this->read(
			self::PREFIX . '/connectors/' . self::DUMMY_CONNECTOR . '/devices/' . self::FOREIGN_DEVICE
			. '/properties/9e8d7c6b-5a49-4382-b716-a5f4e3d2c1b0',
		);

		$parent = $this->follow($document, 'parent');

		self::assertSame('bbcccf8c-33ab-431b-a795-d7bb38b6b6db', $this->dataId($parent));
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	public function testMappedChannelPropertyParentOnAnotherDevice(): void
	{
		$document = $this->read(
			self::PREFIX . '/devices/' . self::SECOND_DEVICE
			. '/channels/bbcccf8c-33ab-431b-a795-d7bb38b6b6db/properties/2b7e4c91-3d5a-4f68-8e19-c0a7b6d5e4f3',
		);

		$parent = $this->follow($document, 'parent');

		self::assertSame('28bc0d38-2f7c-4a71-aa74-27b102f8df4c', $this->dataId($parent));
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	public function testConnectorScopedDeviceRelationships(): void
	{
		$document = $this->read(
			self::PREFIX . '/connectors/' . self::GENERIC_CONNECTOR . '/devices/' . self::FIRST_DEVICE,
		);

		foreach (['channels', 'parents', 'children'] as $relationship) {
			$this->follow($document, $relationship);
		}
	}

	/**
	 * @return array<mixed>
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	private function read(string $url): array
	{
		$router = $this->getContainer()->getByType(Routing\Router::class);

		$response = $router->handle(new ServerRequest(
			RequestMethodInterface::METHOD_GET,
			$url,
			['authorization' => 'Bearer ' . self::VALID_TOKEN],
		));

		self::assertTrue($response instanceof Http\Response);
		self::assertSame(
			StatusCodeInterface::STATUS_OK,
			$response->getStatusCode(),
			sprintf('GET %s answered %d', $url, $response->getStatusCode()),
		);

		return Tests\Tools\JsonAssert::jsonDecode((string) $response->getBody(), $url);
	}

	/**
	 * Follows the relationship's related link and returns the document it answers with
	 *
	 * @param array<mixed> $document
	 *
	 * @return array<mixed>
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	private function follow(array $document, string $relationship): array
	{
		$data = $document['data'] ?? null;
		self::assertTrue(is_array($data));

		$relationships = $data['relationships'] ?? null;
		self::assertTrue(is_array($relationships));

		$entry = $relationships[$relationship] ?? null;
		self::assertTrue(is_array($entry), sprintf('Relationship "%s" is missing', $relationship));

		$links = $entry['links'] ?? null;
		self::assertTrue(is_array($links), sprintf('Relationship "%s" has no links', $relationship));

		$related = $links['related'] ?? null;

		if (is_array($related)) {
			$related = $related['href'] ?? null;
		}

		self::assertTrue(is_string($related), sprintf('Relationship "%s" has no related link', $relationship));

		return $this->read($related);
	}

	/**
	 * @param array<mixed> $document
	 */
	private function dataId(array $document): string|null
	{
		$data = $document['data'] ?? null;

		if (!is_array($data)) {
			return null;
		}

		$id = $data['id'] ?? null;

		return is_string($id) ? $id : null;
	}

}
