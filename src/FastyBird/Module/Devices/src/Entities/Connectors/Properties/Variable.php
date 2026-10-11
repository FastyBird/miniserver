<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Entities\Connectors\Properties;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Types;

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

}
