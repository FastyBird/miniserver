<?php declare(strict_types = 1);

/**
 * ChannelAttribute.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           05.03.20
 */

namespace FastyBird\Connector\FbMqtt\Queue\Messages;

use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;

/**
 * Channel attribute
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelAttribute extends Attribute
{

	public const array ALLOWED_ATTRIBUTES = [
		self::NAME,
		self::PROPERTIES,
		self::CONTROLS,
	];

	public function __construct(
		Uuid\UuidInterface $connector,
		string $device,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $channel,
		#[ObjectMapper\Rules\ArrayEnumValue(cases: self::ALLOWED_ATTRIBUTES)]
		private readonly string $attribute,
		string $value,
		bool $retained = false,
	)
	{
		parent::__construct($connector, $device, $value, $retained);
	}

	public function getChannel(): string
	{
		return $this->channel;
	}

	public function getAttribute(): string
	{
		return $this->attribute;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge([
			'channel' => $this->getChannel(),
		], parent::toArray());
	}

}
