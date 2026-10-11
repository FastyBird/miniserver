<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Queue\Messages;

use Orisai\ObjectMapper;

/**
 * Created device hdmi message
 */
final readonly class DeviceHdmi implements Message
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private int $id,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $name,
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
		];
	}

}
