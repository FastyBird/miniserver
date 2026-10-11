<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Queue\Messages;

use Orisai\ObjectMapper;
use Override;
use Ramsey\Uuid;

/**
 * Device attribute
 */
final class DeviceAttribute extends Attribute
{

	public const array ALLOWED_ATTRIBUTES = [
		self::NAME,
		self::STATE,
		self::PROPERTIES,
		self::CHANNELS,
		self::EXTENSIONS,
		self::CONTROLS,
	];

	public function __construct(
		Uuid\UuidInterface $connector,
		string $device,
		#[ObjectMapper\Rules\ArrayEnumValue(cases: self::ALLOWED_ATTRIBUTES)]
		private readonly string $attribute,
		string $value,
		bool $retained = false,
	)
	{
		parent::__construct($connector, $device, $value, $retained);
	}

	#[Override]
	public function getAttribute(): string
	{
		return $this->attribute;
	}

}
