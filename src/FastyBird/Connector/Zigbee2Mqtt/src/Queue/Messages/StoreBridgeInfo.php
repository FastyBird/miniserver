<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Messages;

use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;

/**
 * Bridge info description message
 */
final class StoreBridgeInfo extends Bridge implements Message
{

	public function __construct(
		Uuid\UuidInterface $connector,
		string $baseTopic,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $version,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $commit,
		#[ObjectMapper\Rules\MappedObjectValue(class: CoordinatorInfo::class)]
		private readonly CoordinatorInfo $coordinator,
	)
	{
		parent::__construct($connector, $baseTopic);
	}

	public function getVersion(): string
	{
		return $this->version;
	}

	public function getCommit(): string
	{
		return $this->commit;
	}

	public function getCoordinator(): CoordinatorInfo
	{
		return $this->coordinator;
	}

	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'version' => $this->getVersion(),
				'commit' => $this->getCommit(),
				'coordinator' => $this->getCoordinator()->toArray(),
			],
		);
	}

}
