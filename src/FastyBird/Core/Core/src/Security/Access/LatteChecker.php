<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Access;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Security\Identity;
use Nette\Utils;
use Override;
use function array_filter;
use function array_key_exists;
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

	public function __construct(private readonly Identity\User $user)
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
		return is_array($element) ? $this->checkUser($element)
				&& $this->checkResourcesPrivileges($element)
				&& $this->checkPermission($element)
				&& $this->checkRoles($element) : true;
	}

	/**
	 * @param array<mixed> $element
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	private function checkUser(array $element): bool
	{
		// Check if element has user parameter
		if (!array_key_exists('user', $element)) {
			return true;
		}

		// Get user parameter
		$user = $element['user'];

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
	 * @param array<mixed> $element
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	protected function checkResourcesPrivileges(array $element): bool
	{
		// Get resources parameter
		$resources = array_key_exists('resource', $element) ? (array) $element['resource'] : null;

		if ($resources !== null && count($resources) > 1) {
			throw new Exceptions\InvalidArgument('Invalid resources count in @Security\Resource annotation!');
		}

		// Get privileges parameter
		$privileges = array_key_exists('privilege', $element) ? (array) $element['privilege'] : null;

		// Check if element has @Secured\Resource or @Secured\Privilege annotation
		if ($resources === null && $privileges === null) {
			return true;
		}

		// Get domain parameter
		$domain = array_key_exists('domain', $element) ? $element['domain'] : null;
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
	 * @param array<mixed> $element
	 *
	 * @throws Exceptions\InvalidState
	 */
	protected function checkPermission(array $element): bool
	{
		// Check if element has permission parameter
		if (!array_key_exists('permission', $element)) {
			return true;
		}

		// Get domain parameter
		$domain = array_key_exists('domain', $element) ? $element['domain'] : null;
		assert(is_string($domain) || $domain === null);
		$domains = $domain !== null ? [$domain] : [];

		$permissions = (array) $element['permission'];

		foreach ($permissions as $permission) {
			assert(is_string($permission));

			// Parse resource & privilege from permission
			[$resource, $privilege] = explode(Checker::PERMISSIONS_DELIMITER, $permission) + ['', ''];

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
	 * @param array<mixed> $element
	 *
	 * @throws Exceptions\InvalidState
	 */
	protected function checkRoles(array $element): bool
	{
		// Check if element has role parameter
		if (array_key_exists('role', $element)) {
			$roles = (array) $element['role'];

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
