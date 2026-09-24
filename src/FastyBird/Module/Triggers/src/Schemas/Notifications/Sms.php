<?php declare(strict_types = 1);

/**
 * Sms.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Module\Triggers\Schemas\Notifications;

use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Triggers\Entities;
use Neomerx\JsonApi;
use function array_merge;

/**
 * SMS notification entity schema
 *
 * @template T of Entities\Notifications\Sms
 * @extends  Notification<T>
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Sms extends Notification
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = Sources\Module::TRIGGERS->value . '/notification/' . Entities\Notifications\Sms::TYPE;

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	public function getEntityClass(): string
	{
		return Entities\Notifications\Sms::class;
	}

	/**
	 * @return iterable<string, string|bool|null>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return array_merge((array) parent::getAttributes($resource, $context), [
			'phone' => $resource->getPhone()->getInternationalNumber(),
		]);
	}

}
