<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\DI;

use FastyBird\Core\Tests\Cases\Unit\BaseTestCase;
use Monolog;
use Nette\DI;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use function array_slice;
use function assert;
use function explode;
use function file;
use function implode;
use function is_array;
use function is_string;
use function ksort;
use function lcfirst;
use function sprintf;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * Core's PSR-20 clock is injected into first-party services only (#655).
 *
 * Several third-party services take an optional ?Psr\Clock\ClockInterface constructor
 * parameter -- Monolog's Logger and Symfony's ArrayAdapter today -- and Nette autowires an
 * optional parameter whenever a service of its type exists. Since #641 Core's clock is that
 * type, so without an explicit `clock: null` they would silently start reading it: the frozen
 * clock in every test container. Whether logs and caches should share the application clock is
 * a product decision, not a side effect of a dependency upgrade.
 *
 * So this reads the compiled Core test container itself: every generated service factory
 * method, whose code is where Nette has written each resolved argument and setup, autowired ones
 * included. A service whose type is outside FastyBird\ and whose factory fetches a
 * fbCore.clock.* service fails the test.
 */
final class ClockConfinementTest extends BaseTestCase
{

	/**
	 * The root namespace segment of every first-party type
	 */
	private const string FIRST_PARTY_VENDOR = 'FastyBird';

	private const string FACTORY_PREFIX = 'createService';

	private const string CLOCK_FACTORY_PREFIX = 'createServiceFbCore__clock__';

	private const string CLOCK_REFERENCE = "\$this->getService('fbCore.clock.";

	/**
	 * @throws ReflectionException
	 */
	public function testNoThirdPartyServiceReceivesTheClock(): void
	{
		$thirdParty = [];
		$firstParty = [];

		foreach ($this->clockConsumers() as $name => $type) {
			if (explode('\\', $type)[0] === self::FIRST_PARTY_VENDOR) {
				$firstParty[$name] = $type;
			} else {
				$thirdParty[$name] = $type;
			}
		}

		self::assertSame(
			[],
			$thirdParty,
			'A third-party service receives Core\'s clock. Give it an explicit `clock: null` (#655),'
			. ' or decide that it should share the application clock.',
		);

		// The known positive: the walk does see the first-party consumers, so an empty list
		// above is not an artefact of a walk that finds nothing
		self::assertArrayHasKey('fbCore.security.token.builder', $firstParty);
		self::assertArrayHasKey('fbCore.security.token.validator', $firstParty);
		self::assertArrayHasKey('fbCore.persistence.utilities.doctrineDateProvider', $firstParty);
	}

	/**
	 * @throws DI\MissingServiceException
	 * @throws ReflectionException
	 */
	public function testTheLoggerAndThePsr6CacheAreGivenNoClock(): void
	{
		$loggerName = $this->container->findByType(Monolog\Logger::class)[0] ?? null;
		self::assertIsString($loggerName);

		foreach ([$loggerName, 'fbCore.cache.psr6'] as $name) {
			self::assertStringContainsString(
				'clock: null',
				$this->factorySource(DI\Container::getMethodName($name)),
				sprintf('%s is not given an explicit `clock: null`', $name),
			);
		}

		$logger = $this->container->getByType(Monolog\Logger::class);
		self::assertNull($logger->getClock());

		$cache = $this->container->getService('fbCore.cache.psr6');
		self::assertInstanceOf(ArrayAdapter::class, $cache);
		self::assertNull((new ReflectionProperty(ArrayAdapter::class, 'clock'))->getValue($cache));
	}

	/**
	 * Service name => type of every service whose generated factory fetches a fbCore.clock.*
	 * service.
	 *
	 * @return array<string, string>
	 *
	 * @throws ReflectionException
	 */
	private function clockConsumers(): array
	{
		$consumers = [];

		foreach ((new ReflectionClass($this->container))->getMethods() as $method) {
			$methodName = $method->getName();

			if (
				!str_starts_with($methodName, self::FACTORY_PREFIX)
				|| str_starts_with($methodName, self::CLOCK_FACTORY_PREFIX)
				|| $method->getDeclaringClass()->getName() === DI\Container::class
			) {
				continue;
			}

			if (!str_contains($this->factorySource($methodName), self::CLOCK_REFERENCE)) {
				continue;
			}

			$type = $method->getReturnType();
			assert($type instanceof ReflectionNamedType);

			// createServiceFbCore__cache__psr6 -> fbCore.cache.psr6
			$consumers[lcfirst(str_replace('__', '.', substr($methodName, strlen(self::FACTORY_PREFIX))))]
				= $type->getName();
		}

		ksort($consumers);

		return $consumers;
	}

	/**
	 * The generated code of one service factory method. Plain assert()s, not PHPUnit
	 * assertions: this runs for every one of the container's factories.
	 *
	 * @throws ReflectionException
	 */
	private function factorySource(string $methodName): string
	{
		$method = new ReflectionMethod($this->container, $methodName);

		$fileName = $method->getFileName();
		assert(is_string($fileName));

		$lines = file($fileName);
		assert(is_array($lines));

		$startLine = $method->getStartLine();
		$endLine = $method->getEndLine();
		assert($startLine !== false && $endLine !== false);

		return implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));
	}

}
