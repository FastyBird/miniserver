<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Types;
use Orisai\ObjectMapper;
use Override;

/**
 * Generation 2 device state message
 */
abstract class DeviceState implements API\Messages\Message
{

	public const string VALUE_NOT_AVAILABLE = 'n/a';

	/**
	 * @param array<string> $errors
	 */
	public function __construct(
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private readonly int $id = 0,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private readonly array $errors = [],
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	abstract public function getType(): Types\ComponentType;

	/**
	 * @return array<string>
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'type' => $this->getType()->value,
			'errors' => $this->getErrors(),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toState(): array
	{
		return [];
	}

}
