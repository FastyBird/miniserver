<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Queue\Messages;

use Orisai\ObjectMapper;

/**
 * Created device application message
 */
final readonly class DeviceApplication implements Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
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
