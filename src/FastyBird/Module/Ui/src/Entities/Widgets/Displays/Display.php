<?php declare(strict_types = 1);

/**
 * Display.php
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

namespace FastyBird\Module\Ui\Entities\Widgets\Displays;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Ui\Entities;
use IPub\DoctrineTimestampable;
use Nette\Utils;
use Ramsey\Uuid;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_ui_module_widgets_display',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'User interface widgets display settings',
	],
)]
#[ORM\Index(columns: ['display_type'], name: 'display_type_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'display_type', type: 'string', length: 100)]
#[ORM\MappedSuperclass]
abstract class Display implements Entities\Entity,
	Entities\EntityParams,
	DoctrineTimestampable\Entities\IEntityCreated, DoctrineTimestampable\Entities\IEntityUpdated
{

	use Entities\TEntity;
	use Entities\TEntityParams;
	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	#[ORM\Id]
	#[ORM\Column(name: 'display_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	protected Uuid\UuidInterface $id;

	#[ORM\OneToOne(
		inversedBy: 'display',
		targetEntity: Entities\Widgets\Widget::class,
		cascade: ['persist', 'remove'],
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
	}

	abstract public static function getType(): string;

	public function getWidget(): Entities\Widgets\Widget
	{
		return $this->widget;
	}

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
