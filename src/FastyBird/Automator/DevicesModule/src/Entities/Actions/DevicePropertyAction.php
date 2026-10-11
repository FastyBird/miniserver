<?php declare(strict_types = 1);

namespace FastyBird\Automator\DevicesModule\Entities\Actions;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Core\Persistence\Mapping\Attribute;
use FastyBird\Module\Triggers\Entities as TriggersEntities;
use Override;
use Ramsey\Uuid;
use function array_merge;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class DevicePropertyAction extends PropertyAction
{

	public const string TYPE = 'device-property';

	#[Attribute\Crud(required: true)]
	#[ORM\Column(name: 'action_device_property', type: Uuid\Doctrine\UuidBinaryType::NAME, nullable: true)]
	private Uuid\UuidInterface $property;

	public function __construct(
		Uuid\UuidInterface $device,
		Uuid\UuidInterface $property,
		string $value,
		TriggersEntities\Triggers\Trigger $trigger,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($device, $value, $trigger, $id);

		$this->property = $property;
	}

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getProperty(): Uuid\UuidInterface
	{
		return $this->property;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'property' => $this->getProperty()->toString(),
		]);
	}

}
