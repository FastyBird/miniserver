<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Security;

/**
 * Fixture for AnnotationCheckerTest -- the class carries no `@Secured\…` annotation, its one
 * method carries a `@Secured\User(loggedIn)` annotation
 */
final class MethodIsSecured
{

	/**
	 * @Secured\User(loggedIn)
	 */
	public function read(): void
	{
		// Only its annotation matters
	}

}
