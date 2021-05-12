<?php declare(strict_types = 1);

/**
 * PropertyRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           02.03.20
 */

namespace FastyBird\MiniServer\Models;

use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\DevicesModule\States as DevicesModuleStates;
use FastyBird\MiniServer\States;
use FastyBird\RedisDbStoragePlugin\Models as RedisDbStoragePluginModels;
use Nette;
use Ramsey\Uuid;

/**
 * Property state repository
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class PropertyRepository implements DevicesModuleModels\States\IPropertyRepository
{

	use Nette\SmartObject;

	/** @var RedisDbStoragePluginModels\IStateRepository */
	private RedisDbStoragePluginModels\IStateRepository $stateRepository;

	public function __construct(
		RedisDbStoragePluginModels\StateRepositoryFactory $stateRepositoryFactory
	) {
		$this->stateRepository = $stateRepositoryFactory->create(States\Property::class);
	}

	/**
	 * {@inheritDoc}
	 */
	public function findOne(
		Uuid\UuidInterface $id
	): ?DevicesModuleStates\IProperty {
		/** @var DevicesModuleStates\IProperty $state */
		$state = $this->stateRepository->findOne($id);

		return $state;
	}

}
