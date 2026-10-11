<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Queue\Messages;

use Orisai\ObjectMapper;
use Override;
use Ramsey\Uuid;
use function array_map;
use function array_merge;

/**
 * Device status message
 */
final class StoreChannelPropertyState extends Device
{

	/**
	 * @param array<DataPointState> $dataPoints
	 */
	public function __construct(
		Uuid\UuidInterface $connector,
		string $identifier,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DataPointState::class),
		)]
		#[ObjectMapper\Modifiers\FieldName('data_points')]
		private readonly array $dataPoints,
	)
	{
		parent::__construct($connector, $identifier);
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
		return array_merge(parent::toArray(), [
			'data_points' => array_map(
				static fn (DataPointState $channel): array => $channel->toArray(),
				$this->getDataPoints(),
			),
		]);
	}

}
