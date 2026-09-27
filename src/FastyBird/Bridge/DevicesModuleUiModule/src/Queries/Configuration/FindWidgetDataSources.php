<?php declare(strict_types = 1);

/**
 * FindWidgetDataSources.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           06.08.24
 */

namespace FastyBird\Bridge\DevicesModuleUiModule\Queries\Configuration;

use FastyBird\Bridge\DevicesModuleUiModule\Documents as DevicesModuleUiModuleDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Ui\Queries as UiQueries;
use Ramsey\Uuid;

/**
 * Find widgets data sources configuration query
 *
 * @template T of DevicesModuleUiModuleDocuments\Widgets\DataSources\Property
 * @extends  UiQueries\Configuration\FindWidgetDataSources<T>
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Queries
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindWidgetDataSources extends UiQueries\Configuration\FindWidgetDataSources
{

	public function forProperty(DevicesDocuments\Property $property): void
	{
		$this->filter[] = '.[?(@.property =~ /(?i).*^' . $property->getId()->toString() . '*$/)]';
	}

	public function byPropertyId(Uuid\UuidInterface $propertyId): void
	{
		$this->filter[] = '.[?(@.property =~ /(?i).*^' . $propertyId->toString() . '*$/)]';
	}

}
