<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\API\Messages\Response;

use FastyBird\Connector\Tuya\API;
use Orisai\ObjectMapper;
use Override;
use function array_map;

/**
 * Report device state message
 */
final readonly class ReportDeviceState implements API\Messages\Message
{

	/**
	 * @param array<DataPointState> $dataPoints
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $identifier,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DataPointState::class),
		)]
		#[ObjectMapper\Modifiers\FieldName('data_points')]
		private array $dataPoints,
	)
	{
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	/**
	 * @return array<DataPointState>
	 */
	public function getDataPoints(): array
	{
		return $this->dataPoints;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'identifier' => $this->getIdentifier(),
			'data_points' => array_map(
				static fn (DataPointState $channel): array => $channel->toArray(),
				$this->getDataPoints(),
			),
		];
	}

}
