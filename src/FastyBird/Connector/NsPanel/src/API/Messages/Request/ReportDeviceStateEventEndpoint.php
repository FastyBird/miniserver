<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\Request;

use FastyBird\Connector\NsPanel\API;
use Orisai\ObjectMapper;
use Override;
use stdClass;

/**
 * Report third-party device state to NS Panel event endpoint request definition
 */
final readonly class ReportDeviceStateEventEndpoint implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('serial_number')]
		private string $serialNumber,
	)
	{
	}

	public function getSerialNumber(): string
	{
		return $this->serialNumber;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'serial_number' => $this->getSerialNumber(),
		];
	}

	#[Override]
	public function toJson(): object
	{
		$json = new stdClass();
		$json->serial_number = $this->getSerialNumber();

		return $json;
	}

}
