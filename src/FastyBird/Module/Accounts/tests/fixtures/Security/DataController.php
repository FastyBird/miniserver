<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Fixtures\Security;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Fixture for ApiKeyAuthenticationTest -- a data route as another module (Devices, Triggers, Ui)
 * declares one: signed-in users with the manager or administrator role
 *
 * @Secured\User(loggedIn)
 */
final class DataController
{

	/**
	 * @Secured\Role(manager,administrator)
	 */
	public function read(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
	{
		return $response->withStatus(StatusCodeInterface::STATUS_OK);
	}

}
