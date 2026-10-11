<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Mapping;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Exceptions;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Mapping;
use Nette\Utils;
use Orisai\ObjectMapper;
use function array_map;
use function in_array;
use function sprintf;

/**
 * Gen2 mapping configuration
 */
readonly class Gen2 implements Mapping\Mapping
{

	/**
	 * @param array<Mapping\Accessories\Accessory> $accessories
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\AnyOf([
				new ObjectMapper\Rules\MappedObjectValue(class: Mapping\Accessories\InputAccessory::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Mapping\Accessories\LightAccessory::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Mapping\Accessories\RollerAccessory::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Mapping\Accessories\SwitchAccessory::class),
			]),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private array $accessories,
	)
	{
	}

	/**
	 * @return array<Mapping\Accessories\Accessory>
	 */
	public function getAccessories(): array
	{
		return $this->accessories;
	}

	/**
	 * @return array<Mapping\Accessories\Accessory>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findForModel(string $model): array
	{
		$accessories = [];

		foreach ($this->accessories as $accessory) {
			if (
				in_array(
					Utils\Strings::lower($model),
					array_map(
						static fn (string $model): string => Utils\Strings::lower($model),
						$accessory->getModels(),
					),
					true,
				)
			) {
				$accessories[] = $accessory;
			}
		}

		if ($accessories === []) {
			throw new Exceptions\InvalidState(
				sprintf('No mapping configuration found for provided device model: %s', $model),
			);
		}

		return $accessories;
	}

}
