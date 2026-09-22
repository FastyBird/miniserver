<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\SimpleAuth\Access;

use FastyBird\Core\Constants as SimpleAuth;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Security\SimpleAuth as Security;
use Nette;
use Nette\Utils;
use Override;
use ReflectionClass;
use ReflectionException;
use Reflector;
use function array_filter;
use function array_key_exists;
use function assert;
use function call_user_func;
use function class_exists;
use function count;
use function end;
use function explode;
use function in_array;
use function is_bool;
use function is_callable;
use function is_string;
use function preg_match_all;
use function preg_quote;
use function preg_split;
use function strtolower;
use function strval;
use const PREG_SPLIT_NO_EMPTY;

/**
 * Presenter & component annotation access checker
 */
final readonly class AnnotationChecker implements Checker, CheckRequirements
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	public function __construct(private readonly Security\User $user)
	{
	}

	/**
	 * @param class-string $className
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function checkAccess(
		string $className,
		string|null $actionName,
	): bool
	{
		try {
			if (class_exists($className)) {
				$reflection = new ReflectionClass($className);

				if ($actionName !== null) {
					foreach ([$reflection, $reflection->getMethod($actionName)] as $element) {
						if (!$this->isAllowed($element)) {
							return false;
						}
					}
				} else {
					if (!$this->isAllowed($reflection)) {
						return false;
					}
				}
			}
		} catch (ReflectionException) {
			return false;
		}

		return true;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	#[Override]
	public function isAllowed(mixed $element): bool
	{
		assert($element instanceof Reflector);

		return $this->checkUser($element)
			&& $this->checkResourcesPrivileges($element)
			&& $this->checkPermission($element)
			&& $this->checkRoles($element);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	private function checkUser(Reflector $element): bool
	{
		// Get user annotation
		$result = $this->parseAnnotation($element, 'Secured\User');

		// Check if element has @Secured\User annotation
		if ($result === null) {
			return true;
		}

		if (count($result) != 1) {
			throw new Exceptions\InvalidArgument('Invalid user count in @Security\User annotation!');
		}

		$userAnnotation = end($result);

		if (!in_array($userAnnotation, ['loggedIn', 'guest'], true)) {
			// Annotation have wrong definition
			throw new Exceptions\InvalidArgument(
				'In @Security\User annotation is allowed only one from two strings: \'loggedIn\' & \'guest\'',
			);
		}

		if ($userAnnotation === 'loggedIn' && $this->user->isLoggedIn() === false) {
			// User have to be logged in and is not
			return false;
		} elseif ($userAnnotation === 'guest' && $this->user->isLoggedIn() === true) {
			// User have to be logged out and is logged in
			return false;
		}

		return true;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	private function checkResourcesPrivileges(Reflector $element): bool
	{
		// Get resource annotation
		$resources = $this->parseAnnotation($element, 'Secured\Resource');

		if ($resources !== null && count($resources) > 1) {
			throw new Exceptions\InvalidArgument('Invalid resources count in @Security\Resource annotation!');
		}

		// Get privileges annotation
		$privileges = $this->parseAnnotation($element, 'Secured\Privilege');

		// Check if element has @Secured\Resource or @Secured\Privilege annotation
		if ($resources === null && $privileges === null) {
			return true;
		}

		$domains = $this->parseAnnotation($element, 'Secured\Domain');
		$domains ??= [];

		if ($resources !== null) {
			foreach ($resources as $resource) {
				if ($privileges !== null) {
					foreach ($privileges as $privilege) {
						$args = array_filter(
							[...$domains, $resource, $privilege],
							static fn (mixed $value): bool => is_string($value) && $value !== '',
						);

						if ($this->user->isAllowed(...$args)) {
							return true;
						}
					}
				} else {
					$args = array_filter(
						[...$domains, $resource],
						static fn (mixed $value): bool => is_string($value) && $value !== '',
					);

					if ($this->user->isAllowed(...$args)) {
						return true;
					}
				}
			}
		} elseif ($privileges !== null) {
			foreach ($privileges as $privilege) {
				$args = array_filter(
					[...$domains, $privilege],
					static fn (mixed $value): bool => is_string($value) && $value !== '',
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
	private function checkPermission(Reflector $element): bool
	{
		// Get permission annotation
		$permissions = $this->parseAnnotation($element, 'Secured\Permission');

		// Check if element has @Secured\Permission annotation
		if ($permissions === null) {
			return true;
		}

		$domains = $this->parseAnnotation($element, 'Secured\Domain');
		$domains ??= [];

		foreach ($permissions as $permission) {
			if (!is_string($permission)) {
				continue;
			}

			// Parse resource & privilege from permission
			[$resource, $privilege] = explode(SimpleAuth\Constants::PERMISSIONS_DELIMITER, $permission) + [null, null];

			// Remove white spaces
			$resource = $resource !== null ? Utils\Strings::trim($resource) : null;
			$privilege = $privilege !== null ? Utils\Strings::trim($privilege) : null;

			$args = array_filter(
				[...$domains, $resource, $privilege],
				static fn (mixed $value): bool => is_string($value) && $value !== '',
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
	private function checkRoles(Reflector $element): bool
	{
		// Check if element has @Secured\Role annotation
		if ($this->parseAnnotation($element, 'Secured\Role') !== null) {
			$rolesAnnotation = $this->parseAnnotation($element, 'Secured\Role');

			foreach ($rolesAnnotation as $role) {
				// Check if role name is defined
				if (is_bool($role) || $role === null) {
					continue;
				}

				if (is_string($role) && $this->user->isInRole($role)) {
					return true;
				}
			}

			return false;
		}

		return true;
	}

	/**
	 * @return array<mixed>|null
	 */
	private function parseAnnotation(Reflector $ref, string $name): array|null
	{
		$callable = [$ref, 'getDocComment'];

		if (!is_callable($callable)) {
			return null;
		}

		$result = preg_match_all(
			'#[\s*]@' . preg_quote($name, '#') . '(?:\(\s*([^)]*)\s*\)|\s|$)#',
			strval(call_user_func($callable)),
			$m,
		);

		if ($result === false || $result === 0) {
			return null;
		}

		static $tokens = ['true' => true, 'false' => false, 'null' => null];

		$res = [];

		foreach ($m[1] as $s) {
			$items = preg_split('#\s*,\s*#', $s, -1, PREG_SPLIT_NO_EMPTY);

			foreach ($items !== false ? $items : ['true'] as $item) {
				$tmp = strtolower($item);

				if (!array_key_exists($tmp, $tokens) && $item !== '') {
					$res[] = $item;
				}
			}
		}

		return $res;
	}

}
