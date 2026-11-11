<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Documents\Widgets\DataSources;

use CuyZ\Valinor\Mapper\Configurator;
use DateTimeInterface;
use FastyBird\Bridge\DevicesModuleUiModule\Entities;
use FastyBird\Core\Documents;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use Override;
use Ramsey\Uuid;
use function array_merge;

#[Documents\Mapping\Document(entity: Entities\Widgets\DataSources\ConnectorProperty::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Widgets\DataSources\ConnectorProperty::TYPE)]
final class ConnectorProperty extends Property
{

	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $widget,
		private readonly Uuid\UuidInterface $connector,
		Uuid\UuidInterface $property,
		#[DevicesDocuments\Mapping\MapStateValue]
		bool|float|int|string|DateTimeInterface|Payloads\Payload|null $value = null,
		#[Documents\Mapping\MapBlankToNull]
		Uuid\UuidInterface|null $owner = null,
		#[Configurator\MapFromKey('created_at')]
		DateTimeInterface|null $createdAt = null,
		#[Configurator\MapFromKey('updated_at')]
		DateTimeInterface|null $updatedAt = null,
	)
	{
		parent::__construct($id, $widget, $property, $value, $owner, $createdAt, $updatedAt);
	}

	#[Override]
	public static function getType(): string
	{
		return Entities\Widgets\DataSources\ConnectorProperty::TYPE;
	}

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	#[Override]
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'connector' => $this->getConnector()->toString(),
		]);
	}

}
