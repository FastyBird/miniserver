<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Queries\Configuration;

use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Types;
use Ramsey\Uuid;

/**
 * Find device variable properties configuration query
 *
 * @template T of Documents\Devices\Properties\Variable
 * @extends  FindDeviceProperties<T>
 */
class FindDeviceVariableProperties extends FindDeviceProperties
{

	public function __construct()
	{
		parent::__construct();

		$this->filter[] = '.[?(@.type == "' . Types\PropertyType::VARIABLE->value . '")]';
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function forParent(Documents\Devices\Properties\Dynamic|Documents\Devices\Properties\Variable $parent): void
	{
		throw new Exceptions\InvalidState('Searching by parent is not allowed for this type of property');
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function byParentId(Uuid\UuidInterface $parentId): void
	{
		throw new Exceptions\InvalidState('Searching by parent is not allowed for this type of property');
	}

	public function byValue(string $value): void
	{
		$this->filter[] = '.[?(@.value =~ /(?i).*^' . $value . '*$/)]';
	}

}
