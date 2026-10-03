<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Helpers;

use FastyBird\Core\Api\Encoding;
use FastyBird\Core\Api\Exceptions as ApiExceptions;
use FastyBird\Core\Exceptions as CoreExceptions;
use Fig\Http\Message\StatusCodeInterface;
use Ramsey\Uuid;

/**
 * Checks that a create request on a nested route names the parent from the URL
 *
 * A route such as `/devices/{device}/properties` already names the parent of the resource
 * being created, so a body relationship naming a different parent is a client error. An
 * omitted relationship, or one with `data: null`, is left to the hydrator's required-relation
 * rule, and so is a document too malformed to read the relationship from.
 */
final class ParentRelationship
{

	/**
	 * @throws ApiExceptions\JsonApiError
	 */
	public static function validate(
		Encoding\IDocument $document,
		string $relationship,
		Uuid\UuidInterface $parentId,
		string $heading,
		string $message,
	): void
	{
		try {
			if (!$document->hasResource()) {
				return;
			}

			$relationships = $document->getResource()->getRelationships();

			if (!$relationships->has($relationship)) {
				return;
			}

			$related = $relationships->get($relationship);

			if ($related->isHasOne() && !$related->hasIdentifier()) {
				return;
			}

			$identifier = $related->isHasOne() ? $related->getIdentifier()->getId() : null;

		} catch (CoreExceptions\InvalidArgument | CoreExceptions\Runtime) {
			return;
		}

		if (
			$identifier !== null
			&& Uuid\Uuid::isValid($identifier)
			&& Uuid\Uuid::fromString($identifier)->equals($parentId)
		) {
			return;
		}

		throw new ApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
			$heading,
			$message,
			[
				'pointer' => '/data/relationships/' . $relationship . '/data/id',
			],
		);
	}

}
