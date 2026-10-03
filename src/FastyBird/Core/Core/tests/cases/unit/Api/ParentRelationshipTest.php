<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Api;

use FastyBird\Core\Api\Encoding;
use FastyBird\Core\Api\Exceptions;
use FastyBird\Core\Api\Helpers;
use FastyBird\Core\Exceptions as CoreExceptions;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid;
use function strtoupper;

/**
 * Pins the shared check behind every nested create (#617): a body relationship naming the URL
 * parent passes, one naming anything else is a 422 pointing at the relationship, and a body that
 * does not name a parent at all is left to the hydrator's required-relation rule.
 */
final class ParentRelationshipTest extends TestCase
{

	private const string PARENT_ID = '69786d15-fd0c-4d9f-9378-33287c2009fa';

	private const string OTHER_ID = 'bf4cd870-2aac-45f0-a85e-e1cefd2d6d9a';

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws Exceptions\JsonApiError
	 */
	#[DataProvider('passing')]
	public function testPasses(string $body): void
	{
		$this->expectNotToPerformAssertions();

		$this->validate($body);
	}

	/**
	 * @return array<string, array<string>>
	 */
	public static function passing(): array
	{
		return [
			'matching parent' => [self::withParent('{"type": "device", "id": "' . self::PARENT_ID . '"}')],
			'matching parent, upper case' => [
				self::withParent('{"type": "device", "id": "' . strtoupper(self::PARENT_ID) . '"}'),
			],
			'no relationships member' => ['{"data": {"type": "property", "attributes": {}}}'],
			'other relationship only' => [
				'{"data": {"type": "property", "relationships": {"parent": {"data": null}}}}',
			],
			'null parent' => [self::withParent('null')],
			'no resource' => ['{"data": null}'],
		];
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 */
	#[DataProvider('rejected')]
	public function testRejects(string $body): void
	{
		try {
			$this->validate($body);

			self::fail('A body naming another parent must be rejected');
		} catch (Exceptions\JsonApiError $ex) {
			self::assertSame(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY, $ex->getCode());
			self::assertSame('Invalid relation', $ex->getMessage());
			self::assertSame('Provided relation is not valid', $ex->getDetail());
			self::assertSame(['pointer' => '/data/relationships/device/data/id'], $ex->getSource());
		}
	}

	/**
	 * @return array<string, array<string>>
	 */
	public static function rejected(): array
	{
		return [
			'other parent' => [self::withParent('{"type": "device", "id": "' . self::OTHER_ID . '"}')],
			'not a uuid' => [self::withParent('{"type": "device", "id": "not-a-uuid"}')],
			'to-many' => [self::withParent('[{"type": "device", "id": "' . self::PARENT_ID . '"}]')],
		];
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws Exceptions\JsonApiError
	 */
	private function validate(string $body): void
	{
		Helpers\ParentRelationship::validate(
			Encoding\Document::create($body),
			'device',
			Uuid\Uuid::fromString(self::PARENT_ID),
			'Invalid relation',
			'Provided relation is not valid',
		);
	}

	private static function withParent(string $data): string
	{
		return '{"data": {"type": "property", "attributes": {}, "relationships": {"device": {"data": '
			. $data
			. '}}}}';
	}

}
