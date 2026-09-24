<?php declare(strict_types = 1);

/**
 * ExtensionAttribute.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           05.07.22
 */

namespace FastyBird\Connector\FbMqtt\Queue\Messages;

use FastyBird\Connector\FbMqtt\Types;
use FastyBird\Core\Persistence\Rules;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Device extension attribute
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ExtensionAttribute implements Message
{

	public const MAC_ADDRESS = 'mac-address';

	public const MANUFACTURER = 'manufacturer';

	public const MODEL = 'model';

	public const VERSION = 'version';

	public const NAME = 'name';

	public const ALLOWED_PARAMETERS = [
		self::MAC_ADDRESS,
		self::MANUFACTURER,
		self::MODEL,
		self::VERSION,
		self::NAME,
	];

	public function __construct(
		#[Rules\UuidValue()]
		private readonly Uuid\UuidInterface $connector,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $device,
		#[ObjectMapper\Rules\InstanceOfValue(type: Types\ExtensionType::class)]
		private readonly Types\ExtensionType $extension,
		#[ObjectMapper\Rules\ArrayEnumValue(cases: self::ALLOWED_PARAMETERS)]
		private readonly string $parameter,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $value,
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $retained = false,
	)
	{
	}

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	public function getDevice(): string
	{
		return $this->device;
	}

	public function getExtension(): Types\ExtensionType
	{
		return $this->extension;
	}

	public function getParameter(): string
	{
		return $this->parameter;
	}

	public function getValue(): string
	{
		return $this->value;
	}

	public function isRetained(): bool
	{
		return $this->retained;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			$this->getParameter() => $this->getValue(),
		];
	}

}
