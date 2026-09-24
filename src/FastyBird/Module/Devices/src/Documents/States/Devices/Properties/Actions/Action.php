<?php declare(strict_types = 1);

/**
 * Action.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           01.06.22
 */

namespace FastyBird\Module\Devices\Documents\States\Devices\Properties\Actions;

use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Persistence\Application\Rules as ApplicationObjectMapper;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Types;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;
use function sprintf;

/**
 * Device property action document
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[CoreDocuments\Mapping\Document]
#[CoreDocuments\Mapping\RoutingMap([
	Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_ACTION_ROUTING_KEY,
])]
final readonly class Action implements CoreDocuments\Document
{

	public function __construct(
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\PropertyAction::class)]
		private Types\PropertyAction $action,
		#[ApplicationObjectMapper\UuidValue()]
		private Uuid\UuidInterface $device,
		#[ApplicationObjectMapper\UuidValue()]
		private Uuid\UuidInterface $property,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: DevicesDocuments\States\ActionValues::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private DevicesDocuments\States\ActionValues|null $set = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: DevicesDocuments\States\ActionValues::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private DevicesDocuments\States\ActionValues|null $write = null,
	)
	{
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->property;
	}

	public function getAction(): Types\PropertyAction
	{
		return $this->action;
	}

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	public function getProperty(): Uuid\UuidInterface
	{
		return $this->property;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getSet(): DevicesDocuments\States\ActionValues|null
	{
		if ($this->getAction() !== Types\PropertyAction::SET) {
			throw new Exceptions\InvalidState(
				sprintf('Write values are available only for action: %s', Types\PropertyAction::SET->value),
			);
		}

		return $this->set;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getWrite(): DevicesDocuments\States\ActionValues|null
	{
		if ($this->getAction() !== Types\PropertyAction::SET) {
			throw new Exceptions\InvalidState(
				sprintf('Write values are available only for action: %s', Types\PropertyAction::SET->value),
			);
		}

		return $this->write;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function toArray(): array
	{
		$data = [
			'id' => $this->getId()->toString(),
			'device' => $this->getDevice()->toString(),
			'property' => $this->getProperty()->toString(),
			'action' => $this->getAction()->value,
		];

		if ($this->getAction() === Types\PropertyAction::SET) {
			if ($this->getSet() !== null) {
				$data = array_merge($data, [
					'set' => $this->getSet()->toArray(),
				]);
			}

			if ($this->getWrite() !== null) {
				$data = array_merge($data, [
					'write' => $this->getWrite()->toArray(),
				]);
			}
		}

		return $data;
	}

}
