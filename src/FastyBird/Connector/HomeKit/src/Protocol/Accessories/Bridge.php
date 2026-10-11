<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Protocol\Accessories;

use FastyBird\Connector\HomeKit;
use FastyBird\Connector\HomeKit\Documents;
use FastyBird\Connector\HomeKit\Exceptions;
use FastyBird\Connector\HomeKit\Types;
use Override;
use Ramsey\Uuid;
use SplObjectStorage;

/**
 * HAP bridge accessory
 */
final class Bridge extends Accessory
{

	/** @var SplObjectStorage<Accessory, null> */
	private SplObjectStorage $accessories;

	public function __construct(
		string $name,
		private readonly Documents\Connectors\Connector $connector,
	)
	{
		parent::__construct(
			$name,
			HomeKit\Constants::STANDALONE_AID,
			Types\AccessoryCategory::BRIDGE,
		);

		$this->accessories = new SplObjectStorage();
	}

	#[Override]
	public function getId(): Uuid\UuidInterface
	{
		return $this->connector->getId();
	}

	public function getConnector(): Documents\Connectors\Connector
	{
		return $this->connector;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function addAccessory(Accessory $accessory): void
	{
		if ($accessory->getCategory() === Types\AccessoryCategory::BRIDGE) {
			throw new Exceptions\InvalidArgument('Bridges cannot be bridged');
		}

		if ($accessory->aid === null) {
			$this->accessories->rewind();

			$newAid = 2;
			$searching = true;

			while ($searching || $newAid > 150) {
				// For some reason AID=7 gets unsupported
				if ($newAid === 7) {
					$newAid++;

					continue;
				}

				foreach ($this->accessories as $existingAccessory) {
					if ($existingAccessory->aid !== null && $existingAccessory->aid === $newAid) {
						$newAid++;

						continue 2;
					}
				}

				$searching = false;
			}

			$accessory->aid = $newAid;
		}

		if ($accessory->aid === $this->aid) {
			throw new Exceptions\InvalidArgument('Accessory added to bridge could not have same AID');
		}

		$this->accessories->rewind();

		foreach ($this->accessories as $existingAccessory) {
			if ($existingAccessory->aid === $accessory->aid) {
				throw new Exceptions\InvalidArgument('Duplicate AID found when attempting to add accessory');
			}
		}

		$this->accessories->offsetSet($accessory);
	}

	/**
	 * @return array<Accessory>
	 */
	public function getAccessories(): array
	{
		$this->accessories->rewind();

		$accessories = [];

		foreach ($this->accessories as $accessory) {
			$accessories[] = $accessory;
		}

		return $accessories;
	}

	public function getAccessory(int $aid): Accessory|null
	{
		$this->accessories->rewind();

		foreach ($this->accessories as $accessory) {
			if ($accessory->aid === $aid) {
				return $accessory;
			}
		}

		return null;
	}

}
