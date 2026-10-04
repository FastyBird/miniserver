<?php declare(strict_types = 1);

namespace FastyBird\MiniServer\Tests\Cases\Application;

use Error;
use JsonException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use function is_array;

/**
 * Characterization, at production scope, of the three JSON:API services that look the schema
 * container up in the DI container when they first need it -- JsonApiMiddleware, Encoding\Builder
 * and Hydrators\Container (#460 §1.5, §3.5). E5.7 (#639) replaces the lookups with constructor
 * injection, made lazy where the schema graph would otherwise be a cycle.
 *
 * Each service is fetched FIRST from its own fresh container, so nothing it depends on has been
 * created before it, and then made to resolve the schema container through its real path: the
 * middleware encodes a JSON:API error, the builder encodes a document, the hydrators container
 * finds the hydrator for a resource type. A cycle introduced by the injection, or a different
 * schema container, shows up here as an error or a different document.
 */
final class JsonApiSchemaContainerTest extends TestCase
{

	use ProductionProbe;

	private const string MEDIA_TYPE = 'application/vnd.api+json';

	/**
	 * @throws Error
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testTheMiddlewareEncodesAnErrorThroughTheSchemaContainer(): void
	{
		self::assertSame(
			[
				'schemaContainers' => ['fbCore.api.schemas.container'],
				'status' => 422,
				'contentType' => self::MEDIA_TYPE,
				'body' => [
					'errors' => [
						[
							'status' => '422',
							'code' => '422',
							'title' => 'E5 probe title',
							'detail' => 'E5 probe detail',
						],
					],
					'jsonapi' => ['version' => '1.1'],
				],
				'schemaContainerCreated' => true,
			],
			$this->jsonApiProbe()['middleware'],
		);
	}

	/**
	 * @throws Error
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testTheBuilderEncodesADocumentThroughTheSchemaContainer(): void
	{
		self::assertSame(
			[
				'status' => 200,
				'contentType' => self::MEDIA_TYPE,
				'body' => [
					'meta' => [
						'author' => 'FastyBird team',
						'copyright' => 'FastyBird s.r.o',
					],
					'jsonapi' => ['version' => '1.1'],
					'links' => ['self' => '/api/v1/e5-probe'],
					'data' => null,
				],
				'schemaContainerCreated' => true,
			],
			$this->jsonApiProbe()['builder'],
		);
	}

	/**
	 * The hydrators container returns the FIRST hydrator whose entity's schema has the
	 * document's type -- for an account that is the profile-account hydrator, which shares the
	 * type with the accounts hydrator and is registered before it.
	 *
	 * @throws Error
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testTheHydratorsContainerFindsTheHydratorThroughTheSchemaContainer(): void
	{
		self::assertSame(
			[
				'found' => [
					'fbAccountsModule.hydrators.accounts' => [
						'type' => 'com.fastybird.accounts-module/account',
						'hydrator' => 'FastyBird\Module\Accounts\Hydrators\Accounts\ProfileAccount',
						'isTheService' => false,
					],
					'fbDevicesModule.hydrators.device.generic' => [
						'type' => 'com.fastybird.devices-module/device/generic',
						'hydrator' => 'FastyBird\Module\Devices\Hydrators\Devices\Generic',
						'isTheService' => true,
					],
					'fbUiModule.hydrators.widgets.analogSensor' => [
						'type' => 'com.fastybird.ui-module/widget/analog-sensor',
						'hydrator' => 'FastyBird\Module\Ui\Hydrators\Widgets\AnalogSensor',
						'isTheService' => true,
					],
				],
				'schemaContainerCreated' => true,
			],
			$this->jsonApiProbe()['hydrators'],
		);
	}

	/**
	 * @return array<mixed>
	 *
	 * @throws Error
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	private function jsonApiProbe(): array
	{
		$result = $this->probe('jsonapi-cold');

		if (!is_array($result)) {
			throw new RuntimeException('The jsonapi-cold probe reported no result');
		}

		return $result;
	}

}
