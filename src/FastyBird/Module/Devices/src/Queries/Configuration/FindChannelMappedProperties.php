<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Queries\Configuration;

use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Types;

/**
 * Find channel mapped properties entities query
 *
 * @template T of Documents\Channels\Properties\Mapped
 * @extends  FindChannelProperties<T>
 */
final class FindChannelMappedProperties extends FindChannelProperties
{

	public function __construct()
	{
		parent::__construct();

		$this->filter[] = '.[?(@.type == "' . Types\PropertyType::MAPPED->value . '")]';
	}

}
