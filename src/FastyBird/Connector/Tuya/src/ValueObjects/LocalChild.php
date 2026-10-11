<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\ValueObjects;

use FastyBird\Connector\Tuya\Types;
use Orisai\ObjectMapper;

/**
 * Local device child
 */
final readonly class LocalChild implements ObjectMapper\MappedObject
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $identifier,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('node_id')]
		private string $nodeId,
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\LocalDeviceType::class)]
		private Types\LocalDeviceType $type,
	)
	{
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getType(): Types\LocalDeviceType
	{
		return $this->type;
	}

	public function getNodeId(): string
	{
		return $this->nodeId;
	}

}
