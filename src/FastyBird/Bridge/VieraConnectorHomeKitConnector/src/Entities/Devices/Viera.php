<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities\Devices;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Exceptions;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Connector\Viera\Entities as VieraEntities;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use Override;
use Ramsey\Uuid;
use function sprintf;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class Viera extends HomeKitEntities\Devices\Device
{

	public const string TYPE = 'viera-connector-homekit-connector-bridge';

	/**
	 * @param array<DevicesEntities\Devices\Device> $parents
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(
		string $identifier,
		HomeKitEntities\Connectors\Connector $connector,
		array $parents,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($identifier, $connector, $name, $id);

		$validParent = false;

		foreach ($parents as $parent) {
			if ($parent instanceof VieraEntities\Devices\Device) {
				$validParent = true;
			}
		}

		if (!$validParent) {
			throw new Exceptions\InvalidArgument(
				sprintf(
					'At least one parent have to be instance of: %s',
					VieraEntities\Devices\Device::class,
				),
			);
		}

		$this->setParents($parents);
	}

	#[Override]
	public static function getType(): string
	{
		return self::TYPE;
	}

	#[Override]
	public function getSource(): Sources\Bridge
	{
		return Sources\Bridge::VIERA_CONNECTOR_HOMEKIT_CONNECTOR;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getParent(): VieraEntities\Devices\Device
	{
		foreach ($this->parents->toArray() as $parent) {
			if ($parent instanceof VieraEntities\Devices\Device) {
				return $parent;
			}
		}

		throw new Exceptions\InvalidState(
			'Bridged viera device have to have parent viera device defined',
		);
	}

}
