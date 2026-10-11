<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Queue\Messages;

use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_map;
use function array_merge;

/**
 * Device state message entity
 */
final class StoreParametersStates extends Device
{

	/**
	 * @param array<States\DeviceParameterState|States\ChannelParameterState> $parameters
	 */
	public function __construct(
		Uuid\UuidInterface $connector,
		string $identifier,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\AnyOf([
				new ObjectMapper\Rules\MappedObjectValue(class: States\DeviceParameterState::class),
				new ObjectMapper\Rules\MappedObjectValue(class: States\ChannelParameterState::class),
			]),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private readonly array $parameters,
	)
	{
		parent::__construct($connector, $identifier);
	}

	/**
	 * @return array<States\DeviceParameterState|States\ChannelParameterState>
	 */
	public function getParameters(): array
	{
		return $this->parameters;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'parameters' => array_map(
				static fn (States\DeviceParameterState|States\ChannelParameterState $parameter): array => $parameter->toArray(),
				$this->getParameters(),
			),
		]);
	}

}
