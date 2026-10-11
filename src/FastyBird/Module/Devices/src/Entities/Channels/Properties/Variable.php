<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Entities\Channels\Properties;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Types;
use Override;
use function array_map;
use function array_merge;
use function sprintf;

#[ORM\Entity]
class Variable extends Property
{

	public const string TYPE = Types\PropertyType::VARIABLE->value;

	public static function getType(): string
	{
		return self::TYPE;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	#[Override]
	public function getParent(): Property|null
	{
		throw new Exceptions\InvalidState(
			sprintf('Parent could not be read for property type: %s', static::getType()),
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	#[Override]
	public function setParent(Property $property): void
	{
		throw new Exceptions\InvalidState(
			sprintf('Parent could not be assigned for property type: %s', static::getType()),
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	#[Override]
	public function removeParent(): void
	{
		throw new Exceptions\InvalidState(
			sprintf('Parent could not be unassigned for property type: %s', static::getType()),
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	#[Override]
	public function setSettable(bool $settable): void
	{
		throw new Exceptions\InvalidState('Settable flag is allowed only for dynamic properties');
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	#[Override]
	public function isSettable(): bool
	{
		throw new Exceptions\InvalidState('Settable flag is allowed only for dynamic properties');
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	#[Override]
	public function setQueryable(bool $queryable): void
	{
		throw new Exceptions\InvalidState('Queryable flag is allowed only for dynamic properties');
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	#[Override]
	public function isQueryable(): bool
	{
		throw new Exceptions\InvalidState('Queryable flag is allowed only for dynamic properties');
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'children' => array_map(
				static fn (Property $child): string => $child->getId()->toString(),
				$this->getChildren(),
			),
		]);
	}

}
