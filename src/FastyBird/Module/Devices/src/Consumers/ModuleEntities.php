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

use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exchange\Consumers;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Caching;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
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
final readonly class ModuleEntities implements Consumers\Consumer
{

	public function __construct(
		private Devices\Logger $logger,
		private Caching\Container $moduleCaching,
		private Helpers\Database $databaseHelper,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function consume(
		Sources\Source $source,
		string $routingKey,
		CoreDocuments\Document|null $document,
	): void
	{
		if ($source === Sources\Module::DEVICES) {
			return;
		}

		$this->databaseHelper->clear();

		if ($document instanceof DevicesDocuments\Connectors\Connector) {
			$this->moduleCaching->getConfigurationBuilderCache()->clean([
				NetteCaching\Cache::Tags => [Types\ConfigurationType::CONNECTORS->value],
			]);

			$this->moduleCaching->getConfigurationRepositoryCache()->clean([
				NetteCaching\Cache::Tags => [
					Types\ConfigurationType::CONNECTORS->value,
					$document->getId()->toString(),
				],
			]);
		} elseif ($document instanceof DevicesDocuments\Connectors\Properties\Property) {
			$this->moduleCaching->getConfigurationBuilderCache()->clean([
				NetteCaching\Cache::Tags => [Types\ConfigurationType::CONNECTORS_PROPERTIES->value],
			]);

			$this->moduleCaching->getConfigurationRepositoryCache()->clean([
				NetteCaching\Cache::Tags => [
					Types\ConfigurationType::CONNECTORS_PROPERTIES->value,
					$document->getId()->toString(),
				],
			]);
		} elseif ($document instanceof DevicesDocuments\Connectors\Controls\Control) {
			$this->moduleCaching->getConfigurationBuilderCache()->clean([
				NetteCaching\Cache::Tags => [Types\ConfigurationType::CONNECTORS_CONTROLS->value],
			]);

			$this->moduleCaching->getConfigurationRepositoryCache()->clean([
				NetteCaching\Cache::Tags => [
					Types\ConfigurationType::CONNECTORS_CONTROLS->value,
					$document->getId()->toString(),
				],
			]);
		} elseif ($document instanceof DevicesDocuments\Devices\Device) {
			$this->moduleCaching->getConfigurationBuilderCache()->clean([
				NetteCaching\Cache::Tags => [Types\ConfigurationType::DEVICES->value],
			]);

			$this->moduleCaching->getConfigurationRepositoryCache()->clean([
				NetteCaching\Cache::Tags => [
					Types\ConfigurationType::DEVICES->value,
					$document->getId()->toString(),
				],
			]);
		} elseif ($document instanceof DevicesDocuments\Devices\Properties\Property) {
			$this->moduleCaching->getConfigurationBuilderCache()->clean([
				NetteCaching\Cache::Tags => [Types\ConfigurationType::DEVICES_PROPERTIES->value],
			]);

			$this->moduleCaching->getConfigurationRepositoryCache()->clean([
				NetteCaching\Cache::Tags => [
					Types\ConfigurationType::DEVICES_PROPERTIES->value,
					$document->getId()->toString(),
				],
			]);
		} elseif ($document instanceof DevicesDocuments\Devices\Controls\Control) {
			$this->moduleCaching->getConfigurationBuilderCache()->clean([
				NetteCaching\Cache::Tags => [Types\ConfigurationType::DEVICES_CONTROLS->value],
			]);

			$this->moduleCaching->getConfigurationRepositoryCache()->clean([
				NetteCaching\Cache::Tags => [
					Types\ConfigurationType::DEVICES_CONTROLS->value,
					$document->getId()->toString(),
				],
			]);
		} elseif ($document instanceof DevicesDocuments\Channels\Channel) {
			$this->moduleCaching->getConfigurationBuilderCache()->clean([
				NetteCaching\Cache::Tags => [Types\ConfigurationType::CHANNELS->value],
			]);

			$this->moduleCaching->getConfigurationRepositoryCache()->clean([
				NetteCaching\Cache::Tags => [
					Types\ConfigurationType::CHANNELS->value,
					$document->getId()->toString(),
				],
			]);
		} elseif ($document instanceof DevicesDocuments\Channels\Properties\Property) {
			$this->moduleCaching->getConfigurationBuilderCache()->clean([
				NetteCaching\Cache::Tags => [Types\ConfigurationType::CHANNELS_PROPERTIES->value],
			]);

			$this->moduleCaching->getConfigurationRepositoryCache()->clean([
				NetteCaching\Cache::Tags => [
					Types\ConfigurationType::CHANNELS_PROPERTIES->value,
					$document->getId()->toString(),
				],
			]);
		} elseif ($document instanceof DevicesDocuments\Channels\Controls\Control) {
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
				'source' => Sources\Module::DEVICES->value,
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
