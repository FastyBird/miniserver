<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Queries\Configuration;

use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Types;

/**
 * Find device mapped properties entities query
 *
 * @template T of Documents\Devices\Properties\Mapped
 * @extends  FindDeviceProperties<T>
 */
final class FindDeviceMappedProperties extends FindDeviceProperties
{

	public function __construct()
	{
		parent::__construct();

		$this->filter[] = '.[?(@.type == "' . Types\PropertyType::MAPPED->value . '")]';
	}

}
