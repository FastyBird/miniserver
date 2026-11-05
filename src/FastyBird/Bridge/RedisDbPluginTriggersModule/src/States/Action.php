<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisDbPluginTriggersModule\States;

use CuyZ\Valinor\Mapper\Configurator;
use DateTimeInterface;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Triggers\States as TriggersStates;
use FastyBird\Plugin\RedisDb\States as RedisDbStates;
use Override;
use Ramsey\Uuid;
use function array_merge;

/**
 * Trigger action state
 */
final class Action extends RedisDbStates\State implements TriggersStates\Action
{

	public function __construct(
		Uuid\UuidInterface $id,
		#[CoreDocuments\Mapping\MapBoolLike]
		private readonly bool $validationResult = false,
		#[CoreDocuments\Mapping\MapBlankToNull]
		#[Configurator\MapFromKey('created_at')]
		private readonly DateTimeInterface|null $createdAt = null,
		#[CoreDocuments\Mapping\MapBlankToNull]
		#[Configurator\MapFromKey('updated_at')]
		private readonly DateTimeInterface|null $updatedAt = null,
	)
	{
		parent::__construct($id);
	}

	#[Override]
	public function isTriggered(): bool
	{
		return $this->validationResult;
	}

	public function getCreatedAt(): DateTimeInterface|null
	{
		return $this->createdAt;
	}

	public function getUpdatedAt(): DateTimeInterface|null
	{
		return $this->updatedAt;
	}

	#[Override]
	public static function getCreateFields(): array
	{
		return [
			0 => 'id',
			'validationResult' => false,
			self::CREATED_AT_FIELD => null,
			self::UPDATED_AT_FIELD => null,
		];
	}

	#[Override]
	public static function getUpdateFields(): array
	{
		return [
			'validationResult',
			self::UPDATED_AT_FIELD,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return array_merge([
			'validation_result' => $this->isTriggered(),
			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		], parent::toArray());
	}

}
