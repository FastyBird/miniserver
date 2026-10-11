<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Queue\Messages;

use FastyBird\Connector\FbMqtt\Helpers;
use FastyBird\Core\Persistence\Rules;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function explode;
use function in_array;
use function strtolower;

/**
 * Device, channel or property attribute
 */
abstract class Attribute implements Message
{

	public const string NAME = 'name';

	public const string PROPERTIES = 'properties';

	public const string STATE = 'state';

	public const string CHANNELS = 'channels';

	public const string EXTENSIONS = 'extensions';

	public const string CONTROLS = 'controls';

	public function __construct(
		#[Rules\UuidValue()]
		private readonly Uuid\UuidInterface $connector,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $device,
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

	abstract public function getAttribute(): string;

	/**
	 * @return string|array<string>
	 */
	public function getValue(): array|string
	{
		return $this->parseValue();
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
			$this->getAttribute() => $this->getValue(),
		];
	}

	/**
	 * @return string|array<string>
	 */
	private function parseValue(): string|array
	{
		if ($this->getAttribute() === self::NAME) {
			return Helpers\Payload::cleanName($this->value);
		} else {
			$value = Helpers\Payload::cleanPayload($this->value);

			if (
				in_array(
					$this->getAttribute(),
					[self::PROPERTIES, self::CHANNELS, self::EXTENSIONS, self::CONTROLS],
					true,
				)
			) {
				$items = array_filter(
					array_map('trim', explode(',', strtolower($value))),
					static fn ($item): bool => $item !== '',
				);

				$items = array_values($items);

				return array_unique($items);
			}
		}

		return $this->value;
	}

}
