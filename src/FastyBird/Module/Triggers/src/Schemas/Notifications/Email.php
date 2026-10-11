<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Schemas\Notifications;

use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Triggers\Entities;
use Neomerx\JsonApi;
use Override;
use function array_merge;

/**
 * Trigger email notification entity schema
 *
 * @template T of Entities\Notifications\Email
 * @extends  Notification<T>
 */
final class Email extends Notification
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Module::TRIGGERS->value . '/notification/' . Entities\Notifications\Email::TYPE;

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	public function getEntityClass(): string
	{
		return Entities\Notifications\Email::class;
	}

	/**
	 * @return iterable<string, string|bool|null>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	#[Override]
	public function getAttributes(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return array_merge((array) parent::getAttributes($resource, $context), [
			'email' => $resource->getEmail(),
		]);
	}

}
