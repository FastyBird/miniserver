<?php declare(strict_types = 1);

/**
 * DevicePropertyAction.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           01.06.22
 */

namespace FastyBird\Automator\DevicesModule\Documents\Actions;

use FastyBird\Automator\DevicesModule\Entities;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Persistence\Application\Rules as ApplicationObjectMapper;
use FastyBird\Module\Triggers\Documents as TriggersDocuments;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;

/**
 * Device property action document
 *
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[CoreDocuments\Mapping\Document(entity: Entities\Actions\DevicePropertyAction::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Actions\DevicePropertyAction::TYPE)]
final class DevicePropertyAction extends TriggersDocuments\Actions\Action
{

	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $trigger,
		bool $enabled,
		#[ApplicationObjectMapper\UuidValue()]
		private readonly Uuid\UuidInterface $device,
		#[ApplicationObjectMapper\UuidValue()]
		private readonly Uuid\UuidInterface $property,
		#[ObjectMapper\Rules\BoolValue()]
		private readonly string $value,
		bool|null $isTriggered = null,
		Uuid\UuidInterface|null $owner = null,
	)
	{
		parent::__construct($id, $trigger, $enabled, $isTriggered, $owner);
	}

	public static function getType(): string
	{
		return Entities\Actions\DevicePropertyAction::TYPE;
	}

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	public function getProperty(): Uuid\UuidInterface
	{
		return $this->property;
	}

	public function getValue(): string
	{
		return $this->value;
	}

	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'device' => $this->getDevice()->toString(),
			'property' => $this->getProperty()->toString(),
			'value' => $this->getValue(),
		]);
	}

}
