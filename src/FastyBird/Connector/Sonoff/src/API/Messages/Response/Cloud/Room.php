<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\API\Messages\Response\Cloud;

use FastyBird\Connector\Sonoff\API;
use Orisai\ObjectMapper;
use Override;

/**
 * User home room entity
 */
final readonly class Room implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $id,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $name,
		#[ObjectMapper\Rules\IntValue()]
		private int $index,
	)
	{
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getIndex(): int
	{
		return $this->index;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
			'index' => $this->getIndex(),
		];
	}

}
