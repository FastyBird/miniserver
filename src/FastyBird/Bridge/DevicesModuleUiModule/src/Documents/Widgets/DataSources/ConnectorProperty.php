<?php declare(strict_types = 1);

/**
 * ConnectorProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           06.08.24
 */

namespace FastyBird\Bridge\DevicesModuleUiModule\Documents\Widgets\DataSources;

use DateTimeInterface;
use FastyBird\Bridge\DevicesModuleUiModule\Entities;
use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Core\Persistence\Application\Rules as ApplicationObjectMapper;
use FastyBird\Core\Values\Types\Payloads;
use Ramsey\Uuid;
use function array_merge;

#[ApplicationDocuments\Mapping\Document(entity: Entities\Widgets\DataSources\ConnectorProperty::class)]
#[ApplicationDocuments\Mapping\DiscriminatorEntry(name: Entities\Widgets\DataSources\ConnectorProperty::TYPE)]
class ConnectorProperty extends Property
{

	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $widget,
		#[ApplicationObjectMapper\UuidValue()]
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

	public static function getType(): string
	{
		return Entities\Widgets\DataSources\ConnectorProperty::TYPE;
	}

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'connector' => $this->getConnector()->toString(),
		]);
	}

}
