<?php declare(strict_types = 1);

/**
 * DevicePropertyCondition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Automator\DevicesModule\Schemas\Conditions;

use FastyBird\Automator\DevicesModule\Entities;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Core\Utilities\Tools as ToolsUtilities;
use FastyBird\Module\Triggers\Schemas as TriggersSchemas;
use Neomerx\JsonApi;
use TypeError;
use ValueError;
use function array_merge;

/**
 * Device property condition entity schema
 *
 * @extends TriggersSchemas\Conditions\Condition<Entities\Conditions\DevicePropertyCondition>
 *
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DevicePropertyCondition extends TriggersSchemas\Conditions\Condition
{

	/**
	 * Define entity schema type string
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const SCHEMA_TYPE = MetadataTypes\Sources\Automator::DEVICE_MODULE->value . '/condition/' . Entities\Conditions\DevicePropertyCondition::TYPE;

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	public function getEntityClass(): string
	{
		return Entities\Conditions\DevicePropertyCondition::class;
	}

	/**
	 * @return iterable<string, string|bool|array<int>|null>
	 *
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return array_merge((array) parent::getAttributes($resource, $context), [
			'device' => $resource->getDevice()->toString(),
			'property' => $resource->getProperty()->toString(),
			'operator' => $resource->getOperator()->value,
			'operand' => ToolsUtilities\Value::toString($resource->getOperand()),
		]);
	}

}
