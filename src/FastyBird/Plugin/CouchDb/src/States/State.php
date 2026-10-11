<?php declare(strict_types = 1);

namespace FastyBird\Plugin\CouchDb\States;

use FastyBird\Core\Persistence\Rules;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Base state
 */
class State implements ObjectMapper\MappedObject
{

	public const string CREATED_AT_FIELD = 'createdAt';

	public const string UPDATED_AT_FIELD = 'updatedAt';

	public function __construct(
		#[Rules\UuidValue()]
		private readonly Uuid\UuidInterface $id,
	)
	{
	}

	/**
	 * @return array<string>|array<int, int|string|bool|null>
	 */
	public static function getCreateFields(): array
	{
		return [
			'id',
		];
	}

	/**
	 * @return array<string>
	 */
	public static function getUpdateFields(): array
	{
		return [];
	}

	/**
	 * @return array<string, mixed|null>
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
		];
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

}
