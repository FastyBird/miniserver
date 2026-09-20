<?php declare(strict_types = 1);

/**
 * ModuleEntities.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           03.09.24
 */

namespace FastyBird\Module\Devices\Consumers;

use FastyBird\Core\Documents\Application as ApplicationDocuments;
use FastyBird\Core\Messaging\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Caching;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Types;
use Nette\Caching as NetteCaching;

/**
 * Exchange to sockets bridge consumer
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class ModuleEntities implements ExchangeConsumers\Consumer
{

	public function __construct(
		private Devices\Logger $logger,
		private Caching\Container $moduleCaching,
		private ToolsHelpers\Database $databaseHelper,
	)
	{
	}

	/**
	 * @throws ToolsExceptions\InvalidState
	 */
	public function consume(
		MetadataTypes\Sources\Source $source,
		string $routingKey,
		ApplicationDocuments\Document|null $document,
	): void
	{
		if ($source === MetadataTypes\Sources\Module::DEVICES) {
			return;
		}

		$this->databaseHelper->clear();

		if ($document instanceof Documents\Connectors\Connector) {
			$this->moduleCaching->getConfigurationBuilderCache()->clean([
				NetteCaching\Cache::Tags => [Types\ConfigurationType::CONNECTORS->value],
			]);

			$this->moduleCaching->getConfigurationRepositoryCache()->clean([
				NetteCaching\Cache::Tags => [
					Types\ConfigurationType::CONNECTORS->value,
					$document->getId()->toString(),
				],
			]);
		} elseif ($document instanceof Documents\Connectors\Properties\Property) {
			$this->moduleCaching->getConfigurationBuilderCache()->clean([
				NetteCaching\Cache::Tags => [Types\ConfigurationType::CONNECTORS_PROPERTIES->value],
			]);

			$this->moduleCaching->getConfigurationRepositoryCache()->clean([
				NetteCaching\Cache::Tags => [
					Types\ConfigurationType::CONNECTORS_PROPERTIES->value,
					$document->getId()->toString(),
				],
			]);
		} elseif ($document instanceof Documents\Connectors\Controls\Control) {
			$this->moduleCaching->getConfigurationBuilderCache()->clean([
				NetteCaching\Cache::Tags => [Types\ConfigurationType::CONNECTORS_CONTROLS->value],
			]);

			$this->moduleCaching->getConfigurationRepositoryCache()->clean([
				NetteCaching\Cache::Tags => [
					Types\ConfigurationType::CONNECTORS_CONTROLS->value,
					$document->getId()->toString(),
				],
			]);
		} elseif ($document instanceof Documents\Devices\Device) {
			$this->moduleCaching->getConfigurationBuilderCache()->clean([
				NetteCaching\Cache::Tags => [Types\ConfigurationType::DEVICES->value],
			]);

			$this->moduleCaching->getConfigurationRepositoryCache()->clean([
				NetteCaching\Cache::Tags => [
					Types\ConfigurationType::DEVICES->value,
					$document->getId()->toString(),
				],
			]);
		} elseif ($document instanceof Documents\Devices\Properties\Property) {
			$this->moduleCaching->getConfigurationBuilderCache()->clean([
				NetteCaching\Cache::Tags => [Types\ConfigurationType::DEVICES_PROPERTIES->value],
			]);

			$this->moduleCaching->getConfigurationRepositoryCache()->clean([
				NetteCaching\Cache::Tags => [
					Types\ConfigurationType::DEVICES_PROPERTIES->value,
					$document->getId()->toString(),
				],
			]);
		} elseif ($document instanceof Documents\Devices\Controls\Control) {
			$this->moduleCaching->getConfigurationBuilderCache()->clean([
				NetteCaching\Cache::Tags => [Types\ConfigurationType::DEVICES_CONTROLS->value],
			]);

			$this->moduleCaching->getConfigurationRepositoryCache()->clean([
				NetteCaching\Cache::Tags => [
					Types\ConfigurationType::DEVICES_CONTROLS->value,
					$document->getId()->toString(),
				],
			]);
		} elseif ($document instanceof Documents\Channels\Channel) {
			$this->moduleCaching->getConfigurationBuilderCache()->clean([
				NetteCaching\Cache::Tags => [Types\ConfigurationType::CHANNELS->value],
			]);

			$this->moduleCaching->getConfigurationRepositoryCache()->clean([
				NetteCaching\Cache::Tags => [
					Types\ConfigurationType::CHANNELS->value,
					$document->getId()->toString(),
				],
			]);
		} elseif ($document instanceof Documents\Channels\Properties\Property) {
			$this->moduleCaching->getConfigurationBuilderCache()->clean([
				NetteCaching\Cache::Tags => [Types\ConfigurationType::CHANNELS_PROPERTIES->value],
			]);

			$this->moduleCaching->getConfigurationRepositoryCache()->clean([
				NetteCaching\Cache::Tags => [
					Types\ConfigurationType::CHANNELS_PROPERTIES->value,
					$document->getId()->toString(),
				],
			]);
		} elseif ($document instanceof Documents\Channels\Controls\Control) {
			$this->moduleCaching->getConfigurationBuilderCache()->clean([
				NetteCaching\Cache::Tags => [Types\ConfigurationType::CHANNELS_CONTROLS->value],
			]);

			$this->moduleCaching->getConfigurationRepositoryCache()->clean([
				NetteCaching\Cache::Tags => [
					Types\ConfigurationType::CHANNELS_CONTROLS->value,
					$document->getId()->toString(),
				],
			]);
		}

		$this->logger->debug(
			'Service cache was cleared',
			[
				'source' => MetadataTypes\Sources\Module::DEVICES->value,
				'type' => 'module-entities-consumer',
				'message' => [
					'routing_key' => $routingKey,
					'source' => $source->value,
					'entity' => $document?->toArray(),
				],
			],
		);
	}

}
