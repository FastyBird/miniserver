<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Queries\Configuration;

use FastyBird\Bridge\DevicesModuleUiModule\Documents as DevicesModuleUiModuleDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use Ramsey\Uuid;

/**
 * Find widgets connector properties data sources configuration query
 *
 * @extends  FindWidgetDataSources<DevicesModuleUiModuleDocuments\Widgets\DataSources\ConnectorProperty>
 */
final class FindWidgetConnectorPropertyDataSources extends FindWidgetDataSources
{

	public function __construct()
	{
		parent::__construct();

		$this->filter[] = '.[?(@.type == "' . DevicesModuleUiModuleDocuments\Widgets\DataSources\ConnectorProperty::getType() . '")]';
	}

	public function forConnector(DevicesDocuments\Property $connector): void
	{
		$this->filter[] = '.[?(@.connector =~ /(?i).*^' . $connector->getId()->toString() . '*$/)]';
	}

	public function byConnectorId(Uuid\UuidInterface $connectorId): void
	{
		$this->filter[] = '.[?(@.connector =~ /(?i).*^' . $connectorId->toString() . '*$/)]';
	}

}
