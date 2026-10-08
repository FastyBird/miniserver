<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence;

/**
 * The entity timestamping settings of the fbCore > persistence > timestampable section,
 * registered by PersistenceExtension as fbCore.persistence.timestampable.configuration
 */
final readonly class TimestampableConfiguration
{

	public function __construct(
		public readonly bool $lazyAssociation = false,
		public readonly bool $autoMapField = false,
		public readonly string $dbFieldType = 'datetime_immutable',
	)
	{
	}

	public function autoMapField(): bool
	{
		return $this->autoMapField === true;
	}

	public function useLazyAssociation(): bool
	{
		return $this->lazyAssociation === true;
	}

}
