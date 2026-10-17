<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Documents\States\Connectors\Properties\Actions;

use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Types;
use Override;
use Ramsey\Uuid;
use function array_merge;
use function sprintf;

/**
 * Connector property action document
 */
#[CoreDocuments\Mapping\Document]
#[CoreDocuments\Mapping\RoutingMap([
	Types\RoutingKey::CONNECTOR_PROPERTY_ACTION->value,
])]
final readonly class Action implements CoreDocuments\Document
{

	public function __construct(
		private Types\PropertyAction $action,
		private Uuid\UuidInterface $connector,
		private Uuid\UuidInterface $property,
		private DevicesDocuments\States\ActionValues|null $set = null,
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

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
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
	#[Override]
	public function toArray(): array
	{
		$data = [
			'id' => $this->getId()->toString(),
			'connector' => $this->getConnector()->toString(),
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
