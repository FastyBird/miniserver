<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Queries\Configuration;

use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Types;
use Override;
use Ramsey\Uuid;

/**
 * Find device dynamic properties entities query
 *
 * @template T of Documents\Devices\Properties\Dynamic
 * @extends  FindDeviceProperties<T>
 */
final class FindDeviceDynamicProperties extends FindDeviceProperties
{

	public function __construct()
	{
		parent::__construct();

		$this->filter[] = '.[?(@.type == "' . Types\PropertyType::DYNAMIC->value . '")]';
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	#[Override]
	public function forParent(Documents\Devices\Properties\Dynamic|Documents\Devices\Properties\Variable $parent): void
	{
		throw new Exceptions\InvalidState('Searching by parent is not allowed for this type of property');
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	#[Override]
	public function byParentId(Uuid\UuidInterface $parentId): void
	{
		throw new Exceptions\InvalidState('Searching by parent is not allowed for this type of property');
	}

}
