<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Hydrators\Emails;

use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Types;

/**
 * Profile email entity hydrator
 */
trait TEmail
{

	public function getEntityName(): string
	{
		return Entities\Emails\Email::class;
	}

	protected function hydrateVisibilityAttribute(
		Objects\StandardObject $attributes,
	): Types\EmailVisibility
	{
		$isPrivate = (bool) $attributes->get('private');

		return $isPrivate ? Types\EmailVisibility::PRIVATE : Types\EmailVisibility::PUBLIC;
	}

}
