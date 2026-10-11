<?php declare(strict_types = 1);

/**
 * Variable.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           04.01.22
 */

namespace FastyBird\Module\Devices\Entities\Channels\Properties;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Types;
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
	public function getParent(): Property|null
	{
		throw new Exceptions\InvalidState(
			sprintf('Parent could not be read for property type: %s', static::getType()),
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setParent(Property $property): void
	{
		throw new Exceptions\InvalidState(
			sprintf('Parent could not be assigned for property type: %s', static::getType()),
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function removeParent(): void
	{
		throw new Exceptions\InvalidState(
			sprintf('Parent could not be unassigned for property type: %s', static::getType()),
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setSettable(bool $settable): void
	{
		throw new Exceptions\InvalidState('Settable flag is allowed only for dynamic properties');
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function isSettable(): bool
	{
		throw new Exceptions\InvalidState('Settable flag is allowed only for dynamic properties');
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setQueryable(bool $queryable): void
	{
		throw new Exceptions\InvalidState('Queryable flag is allowed only for dynamic properties');
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function isQueryable(): bool
	{
		throw new Exceptions\InvalidState('Queryable flag is allowed only for dynamic properties');
	}

	/**
	 * {@inheritDoc}
	 */
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
