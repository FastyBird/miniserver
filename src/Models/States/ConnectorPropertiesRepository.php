<?php declare(strict_types = 1);

/**
 * ConnectorPropertiesRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 * @since          0.2.0
 *
 * @date           29.03.22
 */

namespace FastyBird\MiniServer\Models\States;

use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\MiniServer\States;
use FastyBird\RedisDbStoragePlugin\Models as RedisDbStoragePluginModels;
use Nette;
use Ramsey\Uuid;

/**
 * Connector property state repository
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ConnectorPropertiesRepository implements DevicesModuleModels\States\IConnectorPropertiesRepository
{

	use Nette\SmartObject;

	/** @var RedisDbStoragePluginModels\IStateRepository */
	protected RedisDbStoragePluginModels\IStateRepository $stateRepository;

	public function __construct(
		RedisDbStoragePluginModels\StateRepositoryFactory $stateRepositoryFactory
	) {
		$this->stateRepository = $stateRepositoryFactory->create(States\ConnectorProperty::class);
	}

	/**
	 * {@inheritDoc}
	 */
	public function findOne(
		$property
	): ?States\IConnectorProperty {
		/** @var States\IConnectorProperty $state */
		$state = $this->stateRepository->findOne($property->getId());

		return $state;
	}

	/**
	 * {@inheritDoc}
	 */
	public function findOneById(
		Uuid\UuidInterface $id
	): ?States\IConnectorProperty {
		/** @var States\IConnectorProperty $state */
		$state = $this->stateRepository->findOne($id);

		return $state;
	}

}
