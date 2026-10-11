<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\API\Messages\Response;

use FastyBird\Connector\Tuya\API;
use Orisai\ObjectMapper;
use Override;

/**
 * Report device online state message
 */
final readonly class ReportDeviceOnline implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $identifier,
		#[ObjectMapper\Rules\BoolValue()]
		private bool $online,
	)
	{
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function isOnline(): bool
	{
		return $this->online;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'identifier' => $this->getIdentifier(),
			'online' => $this->isOnline(),
		];
	}

}
