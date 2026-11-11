<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Mapping;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Exceptions;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Mapping;
use Nette\Utils;
use function array_map;
use function in_array;
use function sprintf;

/**
 * Gen1 mapping configuration
 */
final readonly class Gen1 implements Mapping\Mapping
{

	/**
	 * @param array<int<0, max>, Mapping\Accessories\LightAccessory|Mapping\Accessories\RollerAccessory|Mapping\Accessories\SwitchAccessory> $accessories
	 */
	public function __construct(private array $accessories)
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
