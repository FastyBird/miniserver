<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           08.02.22
 */

namespace FastyBird\Module\Devices\Entities\Connectors\Properties;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use IPub\DoctrineCrud\Mapping\Attribute as IPubDoctrine;
use Nette\Utils;
use Ramsey\Uuid;
use TypeError;
use ValueError;
use function array_merge;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_devices_module_connectors_properties',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'Connectors properties',
	],
)]
#[ORM\Index(columns: ['property_identifier'], name: 'property_identifier_idx')]
#[ORM\Index(columns: ['property_settable'], name: 'property_settable_idx')]
#[ORM\Index(columns: ['property_queryable'], name: 'property_queryable_idx')]
#[ORM\UniqueConstraint(name: 'property_identifier_unique', columns: ['property_identifier', 'connector_id'])]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'property_type', type: 'string', length: 100)]
#[ORM\DiscriminatorMap([
	Entities\Connectors\Properties\Variable::TYPE => Entities\Connectors\Properties\Variable::class,
	Entities\Connectors\Properties\Dynamic::TYPE => Entities\Connectors\Properties\Dynamic::class,
])]
abstract class Property extends Entities\Property
{

	#[IPubDoctrine\Crud(required: true)]
	#[ORM\ManyToOne(
		targetEntity: Entities\Connectors\Connector::class,
		cascade: ['persist'],
		inversedBy: 'properties',
	)]
	#[ORM\JoinColumn(
		name: 'connector_id',
		referencedColumnName: 'connector_id',
		nullable: false,
		onDelete: 'CASCADE',
	)]
	protected Entities\Connectors\Connector $connector;

	public function __construct(
		Entities\Connectors\Connector $connector,
		string $identifier,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($identifier, $id);

		$this->connector = $connector;

		$connector->addProperty($this);
	}

	public function getConnector(): Entities\Connectors\Connector
	{
		return $this->connector;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'connector' => $this->getConnector()->getId()->toString(),

			'owner' => $this->getConnector()->getOwnerId(),
		]);
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws Utils\JsonException
	 * @throws TypeError
	 * @throws ValueError
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function __toString(): string
	{
		return Utils\Json::encode($this->toArray());
	}

}
