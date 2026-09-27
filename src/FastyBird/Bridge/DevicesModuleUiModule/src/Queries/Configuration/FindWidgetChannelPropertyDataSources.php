<?php declare(strict_types = 1);

/**
 * FindWidgetChannelPropertyDataSources.php
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
use Ramsey\Uuid;

/**
 * Find widgets channel properties data sources configuration query
 *
 * @extends  FindWidgetDataSources<DevicesModuleUiModuleDocuments\Widgets\DataSources\ChannelProperty>
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Queries
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindWidgetChannelPropertyDataSources extends FindWidgetDataSources
{

	public function __construct()
	{
		parent::__construct();

		$this->filter[] = '.[?(@.type == "' . DevicesModuleUiModuleDocuments\Widgets\DataSources\ChannelProperty::getType() . '")]';
	}

	public function forChannel(DevicesDocuments\Property $channel): void
	{
		$this->filter[] = '.[?(@.channel =~ /(?i).*^' . $channel->getId()->toString() . '*$/)]';
	}

	public function byChannelId(Uuid\UuidInterface $channelId): void
	{
		$this->filter[] = '.[?(@.channel =~ /(?i).*^' . $channelId->toString() . '*$/)]';
	}

}
