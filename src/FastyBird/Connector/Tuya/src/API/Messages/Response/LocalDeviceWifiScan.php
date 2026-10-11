<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\API\Messages\Response;

use FastyBird\Connector\Tuya\API;
use Orisai\ObjectMapper;
use Override;

/**
 * Device wifi scan result message
 */
final readonly class LocalDeviceWifiScan implements API\Messages\Message
{

	/**
	 * @param array<string> $ssids
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $identifier,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\StringValue(notEmpty: true),
		)]
		private array $ssids,
	)
	{
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	/**
	 * @return array<string>
	 */
	public function getSsids(): array
	{
		return $this->ssids;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'identifier' => $this->getIdentifier(),
			'ssids' => $this->getSsids(),
		];
	}

}
