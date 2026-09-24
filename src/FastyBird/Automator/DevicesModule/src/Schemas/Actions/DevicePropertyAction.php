<?php declare(strict_types = 1);

/**
 * DevicePropertyAction.php
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

namespace FastyBird\Automator\DevicesModule\Schemas\Actions;

use FastyBird\Automator\DevicesModule\Entities;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Triggers\Schemas as TriggersSchemas;
use Neomerx\JsonApi;
use TypeError;
use ValueError;
use function array_merge;

/**
 * Trigger device state action entity schema
 *
 * @extends TriggersSchemas\Actions\Action<Entities\Actions\DevicePropertyAction>
 *
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DevicePropertyAction extends TriggersSchemas\Actions\Action
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = Sources\Automator::DEVICE_MODULE->value . '/action/' . Entities\Actions\DevicePropertyAction::TYPE;

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	public function getEntityClass(): string
	{
		return Entities\Actions\DevicePropertyAction::class;
	}

	/**
	 * @return iterable<string, string|bool|null>
	 *
	 * @throws Exceptions\InvalidArgument
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
			'value' => Utilities\Value::toString($resource->getValue()),
		]);
	}

}
