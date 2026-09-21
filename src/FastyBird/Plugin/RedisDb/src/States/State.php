<?php declare(strict_types = 1);

/**
 * State.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     States
 * @since          1.0.0
 *
 * @date           08.03.20
 */

namespace FastyBird\Plugin\RedisDb\States;

use FastyBird\Core\Persistence\Application\Rules as ApplicationObjectMapper;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Base state
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     States
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class State implements ObjectMapper\MappedObject
{

	public const ID_FIELD = 'id';

	public const CREATED_AT_FIELD = 'created_at';

	public const UPDATED_AT_FIELD = 'updated_at';

	public function __construct(
		#[ApplicationObjectMapper\UuidValue()]
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
			self::ID_FIELD,
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
