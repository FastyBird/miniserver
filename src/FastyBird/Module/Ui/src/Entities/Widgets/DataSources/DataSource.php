<?php declare(strict_types = 1);

/**
 * DataSources.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           25.05.20
 */

namespace FastyBird\Module\Ui\Entities\Widgets\DataSources;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\DoctrineCrud;
use FastyBird\Library\DoctrineTimestampable;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Ui\Entities;
use Nette\Utils;
use Ramsey\Uuid;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_ui_module_widgets_data_sources',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'User interface widgets data sources',
	],
)]
#[ORM\Index(columns: ['data_source_type'], name: 'data_source_type_idx')]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'data_source_type', type: 'string', length: 100)]
// Seeded with a subtype this module owns; the rest arrive at runtime from
// Core\Application's EntityDiscriminator. The map has to be non-empty: ClassMetadataFactory
// calls addDefaultDiscriminatorMap() before dispatching loadClassMetadata and only when the
// map is empty, and that default keys entries on short class names. An explicit map skips
// it, which is what the doctrine/orm patch used to do by deferring the call.
#[ORM\DiscriminatorMap([Entities\Widgets\DataSources\Generic::TYPE => Entities\Widgets\DataSources\Generic::class])]
#[ORM\MappedSuperclass]
abstract class DataSource implements Entities\Entity,
	Entities\EntityParams,
	DoctrineTimestampable\Entities\IEntityCreated, DoctrineTimestampable\Entities\IEntityUpdated
{

	use Entities\TEntity;
	use Entities\TEntityParams;
	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	#[ORM\Id]
	#[ORM\Column(name: 'data_source_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	protected Uuid\UuidInterface $id;

	#[ORM\ManyToOne(
		targetEntity: Entities\Widgets\Widget::class,
		cascade: ['persist'],
		inversedBy: 'dataSources',
	)]
	#[ORM\JoinColumn(
		name: 'widget_id',
		referencedColumnName: 'widget_id',
		nullable: false,
		onDelete: 'CASCADE',
	)]
	protected Entities\Widgets\Widget $widget;

	public function __construct(
		Entities\Widgets\Widget $widget,
		Uuid\UuidInterface|null $id = null,
	)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->widget = $widget;

		$widget->addDataSource($this);
	}

	abstract public static function getType(): string;

	public function getWidget(): Entities\Widgets\Widget
	{
		return $this->widget;
	}

	abstract public function hasRelation(string $relation): bool;

	abstract public function getRelation(string $relation): DoctrineCrud\Entities\IEntity|null;

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'type' => static::getType(),

			'widget' => $this->getWidget()->getId()->toString(),

			'owner' => $this->getWidget()->getOwnerId(),
			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		];
	}

	public function getSource(): MetadataTypes\Sources\Source
	{
		return MetadataTypes\Sources\Module::UI;
	}

	/**
	 * @throws Utils\JsonException
	 */
	public function __toString(): string
	{
		return Utils\Json::encode($this->toArray());
	}

}
