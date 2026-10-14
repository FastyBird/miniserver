<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Security;

use FastyBird\Core\Security\Identity;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Helpers;
use FastyBird\Module\Accounts\Security;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;
use SensitiveParameter;
use SplFileInfo;
use UnexpectedValueException;
use function array_keys;
use function class_exists;
use function enum_exists;
use function implode;
use function in_array;
use function interface_exists;
use function is_dir;
use function realpath;
use function sort;
use function str_replace;
use function strlen;
use function strtolower;
use function substr;
use function trait_exists;

/**
 * Every parameter of Core's Security capability and of this module that is named for a password
 * or for the credentials of a sign-in is marked #[\SensitiveParameter], so PHP hands a stack trace
 * a \SensitiveParameterValue in its place, which neither a blue screen nor a Sentry event shows.
 * PasswordInErrorReportsTest shows the effect on the frames a failing sign-in leaves; this covers
 * the parameters an error rarely passes through, and any added later.
 */
final class PasswordParametersTest extends TestCase
{

	private const array NAMES = ['password', 'rawpassword', 'credentials'];

	/**
	 * Namespace prefix => the directory its classes are read from
	 */
	private const array SOURCES = [
		'FastyBird\\Core\\Security\\' => __DIR__ . '/../../../../../../Core/Core/src/Security',
		'FastyBird\\Module\\Accounts\\' => __DIR__ . '/../../../../src',
	];

	/**
	 * @throws ReflectionException
	 * @throws UnexpectedValueException
	 */
	public function testEveryPasswordParameterIsMarkedSensitive(): void
	{
		$parameters = $this->passwordParameters();

		// The scan reaches the parameters along a sign-in, a password change and the helper's
		self::assertArrayHasKey(Identity\User::class . '::login($password)', $parameters);
		self::assertArrayHasKey(Identity\Authenticator::class . '::authenticate($credentials)', $parameters);
		self::assertArrayHasKey(Security\Authenticator::class . '::authenticate($credentials)', $parameters);
		self::assertArrayHasKey(Entities\Identities\Identity::class . '::verifyPassword($rawPassword)', $parameters);
		self::assertArrayHasKey(Entities\Identities\Identity::class . '::setPassword($password)', $parameters);
		self::assertArrayHasKey(Helpers\Password::class . '::isEqual($password)', $parameters);
		self::assertArrayHasKey(Helpers\Password::class . '::hashPassword($password)', $parameters);

		$unmarked = [];

		foreach ($parameters as $name => $marked) {
			if (!$marked) {
				$unmarked[] = $name;
			}
		}

		self::assertSame([], $unmarked, 'Not marked #[\SensitiveParameter]: ' . implode(', ', $unmarked));
	}

	/**
	 * Every parameter of a method declared in the sources that is named for a password, and
	 * whether it is marked
	 *
	 * @return array<string, bool>
	 *
	 * @throws ReflectionException
	 * @throws UnexpectedValueException
	 */
	private function passwordParameters(): array
	{
		$parameters = [];

		foreach ($this->classes() as $class) {
			$reflection = new ReflectionClass($class);

			foreach ($reflection->getMethods() as $method) {
				if ($method->getDeclaringClass()->getName() !== $reflection->getName()) {
					continue;
				}

				foreach ($method->getParameters() as $parameter) {
					if (!in_array(strtolower($parameter->getName()), self::NAMES, true)) {
						continue;
					}

					$name = $reflection->getName() . '::' . $method->getName() . '($' . $parameter->getName() . ')';

					$parameters[$name] = $parameter->getAttributes(SensitiveParameter::class) !== [];
				}
			}
		}

		return $parameters;
	}

	/**
	 * @return list<class-string>
	 *
	 * @throws UnexpectedValueException
	 */
	private function classes(): array
	{
		$classes = [];

		foreach (self::SOURCES as $prefix => $directory) {
			$directory = realpath($directory);
			self::assertTrue($directory !== false && is_dir($directory));

			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

			foreach ($files as $file) {
				if (!$file instanceof SplFileInfo || $file->getExtension() !== 'php') {
					continue;
				}

				$class = $prefix . str_replace(
					'/',
					'\\',
					substr($file->getPathname(), strlen($directory) + 1, -strlen('.php')),
				);

				if (
					class_exists($class)
					|| interface_exists($class)
					|| trait_exists($class)
					|| enum_exists($class)
				) {
					$classes[$class] = true;
				}
			}
		}

		$classes = array_keys($classes);
		sort($classes);

		return $classes;
	}

}
