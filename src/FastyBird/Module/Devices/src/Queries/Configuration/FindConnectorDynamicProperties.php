<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Queries\Configuration;

use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Types;

/**
 * Find connector dynamic properties entities query
 *
 * @template T of Documents\Connectors\Properties\Dynamic
 * @extends  FindConnectorProperties<T>
 */
final class FindConnectorDynamicProperties extends FindConnectorProperties
{

	public function __construct()
	{
		parent::__construct();

		$this->filter[] = '.[?(@.type == "' . Types\PropertyType::DYNAMIC->value . '")]';
	}

}
