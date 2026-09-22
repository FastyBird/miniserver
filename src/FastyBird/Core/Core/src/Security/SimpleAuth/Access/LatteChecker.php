<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\SimpleAuth\Access;

use FastyBird\Core\Constants as SimpleAuth;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Security\SimpleAuth as Security;
use Nette;
use Nette\Utils;
use Override;
use function array_filter;
use function assert;
use function count;
use function explode;
use function in_array;
use function is_array;
use function is_string;

/**
 * Latte helper for access checking
 */
final readonly class LatteChecker implements Checker
{

	use Nette\SmartObject;

	public function __construct(private readonly Security\User $user)
	{
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	#[Override]
	public function isAllowed(mixed $element): bool
	{
		// Check annotations only if element have to be secured
		if (is_array($element)) {
			$element = Utils\ArrayHash::from($element);

			return $this->checkUser($element)
				&& $this->checkResourcesPrivileges($element)
				&& $this->checkPermission($element)
				&& $this->checkRoles($element);
		} else {
			return true;
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	private function checkUser(Utils\ArrayHash $element): bool
	{
		// Check if element has user parameter
		if (!$element->offsetExists('user')) {
			return true;
		}

		// Get user parameter
		$user = $element->offsetGet('user');

		// Parameter is single string
		if (!in_array($user, ['loggedIn', 'guest'], true)) {
			throw new Exceptions\InvalidArgument(
				'In parameter \'user\' is allowed only one from two strings: \'loggedIn\' & \'guest\'',
			);
		}

		if ($user === 'loggedIn' && $this->user->isLoggedIn() === false) {
			// User have to be logged in and is not
			return false;
		} elseif ($user === 'guest' && $this->user->isLoggedIn() === true) {
			// User have to be logged out and is logged in
			return false;
		}

		return true;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	protected function checkResourcesPrivileges(Utils\ArrayHash $element): bool
	{
		// Get resources parameter
		$resources = $element->offsetExists('resource') ? (array) $element->offsetGet('resource') : null;

		if ($resources !== null && count($resources) > 1) {
			throw new Exceptions\InvalidArgument('Invalid resources count in @Security\Resource annotation!');
		}

		// Get privileges parameter
		$privileges = $element->offsetExists('privilege') ? (array) $element->offsetGet('privilege') : null;

		// Check if element has @Secured\Resource or @Secured\Privilege annotation
		if ($resources === null && $privileges === null) {
			return true;
		}

		// Get domain parameter
		$domain = $element->offsetExists('domain') ? $element->offsetGet('domain') : null;
		assert(is_string($domain) || $domain === null);
		$domains = $domain !== null ? [$domain] : [];

		if ($resources !== null) {
			foreach ($resources as $resource) {
				assert(is_string($resource));

				if ($privileges !== null) {
					foreach ($privileges as $privilege) {
						assert(is_string($privilege));

						$args = array_filter(
							[...$domains, $resource, $privilege],
							static fn (string|null $value): bool => $value !== '',
						);

						if ($this->user->isAllowed(...$args)) {
							return true;
						}
					}
				} else {
					$args = array_filter(
						[...$domains, $resource],
						static fn (string|null $value): bool => $value !== '',
					);

					if ($this->user->isAllowed(...$args)) {
						return true;
					}
				}
			}
		} elseif ($privileges !== null) {
			foreach ($privileges as $privilege) {
				assert(is_string($privilege));

				$args = array_filter(
					[...$domains, $privilege],
					static fn (string|null $value): bool => $value !== '',
				);

				if ($this->user->isAllowed(...$args)) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	protected function checkPermission(Utils\ArrayHash $element): bool
	{
		// Check if element has permission parameter
		if (!$element->offsetExists('permission')) {
			return true;
		}

		// Get domain parameter
		$domain = $element->offsetExists('domain') ? $element->offsetGet('domain') : null;
		assert(is_string($domain) || $domain === null);
		$domains = $domain !== null ? [$domain] : [];

		$permissions = (array) $element->offsetGet('permission');

		foreach ($permissions as $permission) {
			assert(is_string($permission));

			// Parse resource & privilege from permission
			[$resource, $privilege] = explode(SimpleAuth\Constants::PERMISSIONS_DELIMITER, $permission) + ['', ''];

			// Remove white spaces
			$resource = Utils\Strings::trim($resource);
			$privilege = Utils\Strings::trim($privilege);

			$args = array_filter(
				[...$domains, $resource, $privilege],
				static fn (string|null $value): bool => $value !== null && $value !== '',
			);

			if ($this->user->isAllowed(...$args)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	protected function checkRoles(Utils\ArrayHash $element): bool
	{
		// Check if element has role parameter
		if ($element->offsetExists('role')) {
			$roles = (array) $element->offsetGet('role');

			foreach ($roles as $role) {
				assert(is_string($role));

				if ($this->user->isInRole($role)) {
					return true;
				}
			}

			return false;
		}

		return true;
	}

}
