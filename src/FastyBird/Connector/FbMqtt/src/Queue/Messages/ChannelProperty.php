<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Queue\Messages;

use Orisai\ObjectMapper;
use Override;
use Ramsey\Uuid;
use function array_merge;

/**
 * Device or channel property
 */
final class ChannelProperty extends Property
{

	/**
	 * @param array<PropertyAttribute> $attributes
	 */
	public function __construct(
		Uuid\UuidInterface $connector,
		string $device,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $channel,
		string $property,
		array $attributes = [],
		string|null $value = Property::VALUE_NOT_SET,
		bool $retained = false,
	)
	{
		parent::__construct($connector, $device, $property, $attributes, $value, $retained);
	}

	public function getChannel(): string
	{
		return $this->channel;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return array_merge([
			'channel' => $this->getChannel(),
		], parent::toArray());
	}

}
