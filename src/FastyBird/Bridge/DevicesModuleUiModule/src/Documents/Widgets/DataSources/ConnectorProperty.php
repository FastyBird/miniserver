<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Documents\Widgets\DataSources;

use DateTimeInterface;
use FastyBird\Bridge\DevicesModuleUiModule\Entities;
use FastyBird\Core\Documents;
use FastyBird\Core\Persistence\Rules;
use FastyBird\Core\Values\Types\Payloads;
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
		#[Rules\UuidValue()]
		private readonly Uuid\UuidInterface $connector,
		Uuid\UuidInterface $property,
		bool|float|int|string|DateTimeInterface|Payloads\Payload|null $value = null,
		Uuid\UuidInterface|null $owner = null,
		DateTimeInterface|null $createdAt = null,
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
