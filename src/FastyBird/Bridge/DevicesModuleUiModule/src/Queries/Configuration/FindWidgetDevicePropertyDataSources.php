<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Queries\Configuration;

use FastyBird\Bridge\DevicesModuleUiModule\Documents as DevicesModuleUiModuleDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use Ramsey\Uuid;

/**
 * Find widgets device properties data sources configuration query
 *
 * @extends  FindWidgetDataSources<DevicesModuleUiModuleDocuments\Widgets\DataSources\DeviceProperty>
 */
final class FindWidgetDevicePropertyDataSources extends FindWidgetDataSources
{

	public function __construct()
	{
		parent::__construct();

		$this->filter[] = '.[?(@.type == "' . DevicesModuleUiModuleDocuments\Widgets\DataSources\DeviceProperty::getType() . '")]';
	}

	public function forDevice(DevicesDocuments\Property $device): void
	{
		$this->filter[] = '.[?(@.device =~ /(?i).*^' . $device->getId()->toString() . '*$/)]';
	}

	public function byDeviceId(Uuid\UuidInterface $deviceId): void
	{
		$this->filter[] = '.[?(@.device =~ /(?i).*^' . $deviceId->toString() . '*$/)]';
	}

}
