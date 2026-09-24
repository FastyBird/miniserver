<?php declare(strict_types = 1);

/**
 * SyncDevicesPayloadEndpoint.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\API\Messages\Response;

use FastyBird\Connector\NsPanel\API;
use FastyBird\Core\Persistence\Rules;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use stdClass;

/**
 * Synchronise third-party devices with NS Panel event payload endpoint response definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class SyncDevicesPayloadEndpoint implements API\Messages\Message
{

	public function __construct(
		#[Rules\UuidValue()]
		#[ObjectMapper\Modifiers\FieldName('third_serial_number')]
		private Uuid\UuidInterface $thirdSerialNumber,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('serial_number')]
		private string $serialNumber,
	)
	{
	}

	public function getThirdSerialNumber(): Uuid\UuidInterface
	{
		return $this->thirdSerialNumber;
	}

	public function getSerialNumber(): string
	{
		return $this->serialNumber;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'third_serial_number' => $this->getThirdSerialNumber()->toString(),
			'serial_number' => $this->getSerialNumber(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->third_serial_number = $this->getThirdSerialNumber()->toString();
		$json->serial_number = $this->getSerialNumber();

		return $json;
	}

}
