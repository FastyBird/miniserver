<?php declare(strict_types = 1);

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
 */
final class Sms extends Notification
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Module::TRIGGERS->value . '/notification/' . Entities\Notifications\Sms::TYPE;

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
