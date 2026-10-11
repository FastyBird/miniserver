<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen1;

use FastyBird\Connector\Shelly\API;
use Orisai\ObjectMapper;
use function array_map;
use function array_unique;
use const SORT_REGULAR;

/**
 * Generation 1 device description message
 */
final readonly class GetDeviceDescription implements API\Messages\Message
{

	/**
	 * @param array<DeviceBlockDescription> $blocks
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceBlockDescription::class),
		)]
		private array $blocks,
	)
	{
	}

	/**
	 * @return array<DeviceBlockDescription>
	 */
	public function getBlocks(): array
	{
		return array_unique($this->blocks, SORT_REGULAR);
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'blocks' => array_map(
				static fn (DeviceBlockDescription $block): array => $block->toArray(),
				$this->getBlocks(),
			),
		];
	}

}
