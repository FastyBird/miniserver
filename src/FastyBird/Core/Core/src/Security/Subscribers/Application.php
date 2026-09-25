<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Subscribers;

use FastyBird\Core\Constants;
use FastyBird\Core\Presenters\Events;
use FastyBird\Core\Security\Exceptions;
use FastyBird\Core\Security\Identity;
use Lcobucci\JWT;
use Nette\Http;
use Override;
use Symfony\Component\EventDispatcher;
use Throwable;
use function is_string;

/**
 * Application UI events
 */
final readonly class Application implements EventDispatcher\EventSubscriberInterface
{

	public function __construct(
		private readonly Identity\IdentityProvider $identityFactory,
		private readonly Identity\User $user,
		private readonly Identity\TokenValidator $tokenValidator,
		private readonly Http\RequestFactory $requestFactory,
	)
	{
	}

	#[Override]
	public static function getSubscribedEvents(): array
	{
		return [
			Events\PresenterRequest::class => 'request',
		];
	}

	public function request(): void
	{
		try {
			$token = $this->getToken();

			if ($token !== null) {
				$identity = $this->identityFactory->create($token);

				if ($identity !== null) {
					$this->user->login($identity);

					return;
				}
			}
		} catch (Throwable) {
			// Just ignore it
		}

		$this->user->logout();
	}

	/**
	 * @throws Exceptions\UnauthorizedAccess
	 */
	private function getToken(): JWT\UnencryptedToken|null
	{
		$request = $this->requestFactory->fromGlobals();

		$token = $request->getCookie(Constants::ACCESS_TOKEN_COOKIE);

		if (is_string($token)) {
			$token = $this->tokenValidator->validate($token);

			if ($token === null) {
				return null;
			}

			return $token;
		}

		return null;
	}

}
