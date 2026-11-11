<?php declare(strict_types = 1);

namespace FastyBird\Automator\DevicesModule\Documents\Actions;

use CuyZ\Valinor\Mapper\Configurator;
use FastyBird\Automator\DevicesModule\Entities;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Triggers\Documents as TriggersDocuments;
use Override;
use Ramsey\Uuid;
use function array_merge;

/**
 * Device channel property action document
 */
#[CoreDocuments\Mapping\Document(entity: Entities\Actions\ChannelPropertyAction::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Actions\ChannelPropertyAction::TYPE)]
final class ChannelPropertyAction extends TriggersDocuments\Actions\Action
{

	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $trigger,
		bool $enabled,
		private readonly Uuid\UuidInterface $device,
		private readonly Uuid\UuidInterface $channel,
		private readonly Uuid\UuidInterface $property,
		private readonly string $value,
		#[CoreDocuments\Mapping\MapBlankToNull]
		#[Configurator\MapFromKey('is_triggered')]
		bool|null $isTriggered = null,
		#[CoreDocuments\Mapping\MapBlankToNull]
		Uuid\UuidInterface|null $owner = null,
	)
	{
		parent::__construct($id, $trigger, $enabled, $isTriggered, $owner);
	}

	#[Override]
	public static function getType(): string
	{
		return Entities\Actions\ChannelPropertyAction::TYPE;
	}

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	public function getChannel(): Uuid\UuidInterface
	{
		return $this->channel;
	}

	public function getProperty(): Uuid\UuidInterface
	{
		return $this->property;
	}

	public function getValue(): string
	{
		return $this->value;
	}

	#[Override]
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'device' => $this->getDevice()->toString(),
			'channel' => $this->getChannel()->toString(),
			'property' => $this->getProperty()->toString(),
			'value' => $this->getValue(),
		]);
	}

}
