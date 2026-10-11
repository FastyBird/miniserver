<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Queue\Messages;

use Orisai\ObjectMapper;
use Override;
use function array_map;
use function array_unique;
use const SORT_REGULAR;

/**
 * Device channel state message
 */
final readonly class ChannelState implements Message
{

	/**
	 * @param array<PropertyState> $sensors
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $identifier,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(PropertyState::class),
		)]
		private array $sensors = [],
	)
	{
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	/**
	 * @return array<PropertyState>
	 */
	public function getSensors(): array
	{
		return array_unique($this->sensors, SORT_REGULAR);
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'identifier' => $this->getIdentifier(),
			'sensors' => array_map(
				static fn (PropertyState $sensor): array => $sensor->toArray(),
				$this->getSensors(),
			),
		];
	}

}
