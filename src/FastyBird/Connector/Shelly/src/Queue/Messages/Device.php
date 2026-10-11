<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Queue\Messages;

use FastyBird\Core\Persistence\Rules;
use Orisai\ObjectMapper;
use Override;
use Ramsey\Uuid;

/**
 * Base device message
 */
abstract class Device implements Message
{

	public function __construct(
		#[Rules\UuidValue()]
		private readonly Uuid\UuidInterface $connector,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $identifier,
	)
	{
	}

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'connector' => $this->getConnector()->toString(),
			'identifier' => $this->getIdentifier(),
		];
	}

}
