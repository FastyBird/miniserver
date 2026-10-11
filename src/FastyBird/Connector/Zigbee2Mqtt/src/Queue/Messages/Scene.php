<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Messages;

use Orisai\ObjectMapper;
use Override;

/**
 * Bridge group scene description message
 */
final readonly class Scene implements Message
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private string $id,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $name,
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

	#[Override]
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
		];
	}

}
