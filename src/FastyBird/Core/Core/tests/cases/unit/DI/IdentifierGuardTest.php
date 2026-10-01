<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\DI;

use FastyBird\Core\DI\CoreExtension;
use FastyBird\Core\Tests;
use Nette\Schema\Elements\Structure;
use Nette\Schema\Schema;
use ReflectionClass;
use ReflectionClassConstant;
use function array_diff;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function explode;
use function file;
use function implode;
use function in_array;
use function is_string;
use function preg_match;
use function sort;
use function sprintf;
use function str_starts_with;
use function strtolower;
use function trim;
use const FILE_IGNORE_NEW_LINES;

/**
 * No Core DI identifier carries the name of a library Core was assembled from, and every one
 * is named for the capability that owns it.
 *
 * `make naming` guards PHP namespaces, type names and imports. It cannot see a service name,
 * a configuration key or a tag, which are strings. This test checks those, following the
 * census of Epic #459 (#553, section 10), with two kinds of rule:
 *
 * - "denylist": a dot-separated segment equals one of DENYLIST, ignoring case (so the
 *   `jsonapi` in fbCore.jsonApi.middlewares.jsonapi counts too). A segment that merely
 *   contains one (classMetadataFactory) does not. POSITIONAL_ENTRY counts only as the
 *   top-level configuration key or as the service segment directly under fbCore. Checked for
 *   service names, every configuration key path (every node of the schema) and tags.
 * - "pattern": a service name has the shape fbCore.<capability>.<role> or is one of the root
 *   forms; a tag has the shape fastybird.core.<capability>.<role>. The denylist cannot see
 *   `document.*` or `consumer_state`; the pattern cannot see fbCore.phone.doctrinePhone.*.
 *
 * The checked identifiers are every Core service name in the compiled Core test container
 * (fbCore.* and the unprefixed document.*), every key path of CoreExtension's schema, and
 * every tag constant of CoreExtension.
 *
 * VIOLATIONS_FILE lists today's violations, one per line as "<kind> <identifier> <rules>". It
 * may only shrink: the test fails on a violation that is not listed, and on a listed entry
 * that no longer occurs exactly as listed (fixed, or its rules changed). Epic #459 empties it.
 */
final class IdentifierGuardTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * The library names, in the camelCase form a DI identifier uses (census section 10). The
	 * census owns this list.
	 */
	public const array DENYLIST = [
		'simpleAuth',
		'slimRouter',
		'doctrineCrud',
		'doctrineOrmQuery',
		'doctrineTimestampable',
		'doctrinePhone',
		'jsonApi',
		'jsonApiDocument',
		'metadata',
		'tools',
		'dateTimeFactory',
		'webServer',
		'wsServer',
		'httpServer',
		'ipub',
		'iPublikuj',
	];

	/**
	 * `application` names the library only where the library put it. Anywhere else
	 * (security.enable.nette.application, fbCore.httpServer.application.classic) it is Nette's
	 * or the HTTP server's Application.
	 */
	public const string POSITIONAL_ENTRY = 'application';

	public const array CAPABILITIES = [
		'api',
		'clock',
		'documents',
		'exchange',
		'http',
		'logging',
		'persistence',
		'phone',
		'security',
		'values',
		'webSockets',
	];

	/**
	 * Root services: named by the root namespace their type lives in, or by bare role
	 */
	public const array ROOT_GROUPS = ['eventLoop', 'ui', 'cache'];

	public const array ROOT_SERVICES = ['fbCore.eventDispatcher', 'fbCore.configuration'];

	/**
	 * Digits are allowed after the first character (fbCore.cache.psr6)
	 */
	public const string ROLE = '[a-z][A-Za-z0-9]*(?:\.[a-z][A-Za-z0-9]*)*';

	/**
	 * Core registers these without the fbCore prefix
	 */
	private const array UNPREFIXED_SERVICES = [
		'document.',
	];

	private const string VIOLATIONS_FILE = __DIR__ . '/identifier-guard-violations.txt';

	/*
	 * Every target name the census (#553) approves: section 2 (services), section 3 (key paths)
	 * and section 4 (tags). Each must pass every rule, or the guard could never reach empty.
	 * A fixture here, not a map file: the maps under tools/di-maps belong to #558 and #559.
	 */

	private const array CENSUS_SERVICE_TARGETS = [
		'fbCore.logging.handler.rotatingFile',
		'fbCore.logging.handler.stdOut',
		'fbCore.logging.handler.console',
		'fbCore.cache.psr6',
		'fbCore.eventLoop.wrapper',
		'fbCore.eventLoop.status',
		'fbCore.logging.subscribers.console',
		'fbCore.persistence.subscribers.entityDiscriminator',
		'fbCore.eventLoop.subscribers.lifeCycle',
		'fbCore.ui.templateFactory',
		'fbCore.ui.routes',
		'fbCore.documents.cache',
		'fbCore.documents.factory',
		'fbCore.documents.mapping.attributeDriver',
		'fbCore.documents.mapping.driverChain',
		'fbCore.documents.mapping.classMetadataFactory',
		'fbCore.exchange.consumer',
		'fbCore.exchange.publisher',
		'fbCore.exchange.publisher.async',
		'fbCore.exchange.entityFactory',
		'fbCore.security.auth',
		'fbCore.security.token.builder',
		'fbCore.security.token.reader',
		'fbCore.security.token.validator',
		'fbCore.security.identityFactory',
		'fbCore.security.userStorage',
		'fbCore.security.access.annotationChecker',
		'fbCore.security.access.latteChecker',
		'fbCore.security.access.linkChecker',
		'fbCore.security.casbin.adapter',
		'fbCore.security.casbin.subscriber',
		'fbCore.security.casbin.enforcerFactory',
		'fbCore.security.middleware.access',
		'fbCore.security.middleware.user',
		'fbCore.security.doctrine.driver',
		'fbCore.security.doctrine.subscriber',
		'fbCore.security.doctrine.tokensRepository',
		'fbCore.security.doctrine.tokensManager',
		'fbCore.security.doctrine.policiesRepository',
		'fbCore.security.doctrine.policiesManager',
		'fbCore.security.nette.application',
		'fbCore.persistence.helpers.database',
		'fbCore.persistence.utilities.doctrineDateProvider',
		'fbCore.values.schemas.validator',
		'fbCore.logging.helpers.sentry',
		'fbCore.logging.sentry.handler',
		'fbCore.logging.sentry.clientBuilder',
		'fbCore.logging.sentry.client',
		'fbCore.logging.sentry.hub',
		'fbCore.clock.system',
		'fbCore.clock.frozen',
		'fbCore.persistence.entity.mapper',
		'fbCore.persistence.entity.creator',
		'fbCore.persistence.entity.updater',
		'fbCore.persistence.entity.deleter',
		'fbCore.persistence.crud',
		'fbCore.configuration',
		'fbCore.persistence.timestampable.driver',
		'fbCore.persistence.timestampable.subscriber',
		'fbCore.persistence.migrations.subscriber',
		'fbCore.api.builder',
		'fbCore.api.middleware',
		'fbCore.api.hydrators.container',
		'fbCore.api.schemas.container',
		'fbCore.api.helpers.crudReader',
		'fbCore.phone.libphone.utils',
		'fbCore.phone.libphone.geoCoder',
		'fbCore.phone.libphone.shortNumber',
		'fbCore.phone.libphone.mapper.carrier',
		'fbCore.phone.libphone.mapper.timezone',
		'fbCore.phone.helper',
		'fbCore.phone.doctrine.subscriber',
		'fbCore.webSockets.controllers.factory',
		'fbCore.webSockets.clients.factory',
		'fbCore.webSockets.clients.driver.memory',
		'fbCore.webSockets.clients.storage',
		'fbCore.webSockets.routing.router',
		'fbCore.webSockets.routing.generator',
		'fbCore.webSockets.server.wrapper',
		'fbCore.webSockets.server.flashWrapper',
		'fbCore.webSockets.server.handlers',
		'fbCore.webSockets.server.loop',
		'fbCore.webSockets.server.configuration',
		'fbCore.webSockets.server.logger',
		'fbCore.webSockets.server.runtime',
		'fbCore.webSockets.wamp.topics.driver.memory',
		'fbCore.webSockets.wamp.topics.storage',
		'fbCore.webSockets.wamp.application',
		'fbCore.webSockets.wamp.serializer',
		'fbCore.webSockets.wamp.pushRegistry',
		'fbCore.webSockets.wamp.clientsFactory',
		'fbCore.webSockets.wamp.subscribers.onServerStart',
		'fbCore.http.routing.responseFactory',
		'fbCore.http.routing.router',
		'fbCore.http.commands.server',
		'fbCore.http.middlewares.cors',
		'fbCore.http.middlewares.staticFiles',
		'fbCore.http.middlewares.router',
		'fbCore.http.application.classic',
		'fbCore.http.server.factory',
		'fbCore.http.subscribers.server',
		'fbCore.webSockets.commands.server',
		'fbCore.webSockets.subscribers.client',
		'fbCore.eventDispatcher',
		'fbCore.security.user',
	];

	private const array CENSUS_KEY_TARGETS = [
		'logging',
		'logging.rotatingFile',
		'logging.rotatingFile.enabled',
		'logging.rotatingFile.level',
		'logging.rotatingFile.filename',
		'logging.stdOut',
		'logging.stdOut.enabled',
		'logging.stdOut.level',
		'logging.console',
		'logging.console.enabled',
		'logging.console.level',
		'documents',
		'documents.mapping',
		'documents.excludePaths',
		'security',
		'security.token',
		'security.token.issuer',
		'security.token.signature',
		'security.enable',
		'security.enable.middleware',
		'security.enable.doctrine',
		'security.enable.doctrine.mapping',
		'security.enable.doctrine.models',
		'security.enable.casbin',
		'security.enable.casbin.database',
		'security.enable.nette',
		'security.enable.nette.application',
		'security.application',
		'security.application.signInUrl',
		'security.application.homeUrl',
		'security.services',
		'security.services.identity',
		'security.casbin',
		'security.casbin.model',
		'security.casbin.policy',
		'logging.sentry',
		'logging.sentry.dsn',
		'logging.sentry.level',
		'clock',
		'clock.timeZone',
		'clock.system',
		'clock.frozen',
		'persistence.timestampable',
		'persistence.timestampable.lazyAssociation',
		'persistence.timestampable.autoMapField',
		'persistence.timestampable.dbFieldType',
		'api',
		'api.meta',
		'api.meta.author',
		'api.meta.copyright',
		'webSockets',
		'webSockets.storage',
		'webSockets.storage.clients',
		'webSockets.storage.clients.driver',
		'webSockets.storage.clients.ttl',
		'webSockets.storage.topics',
		'webSockets.storage.topics.driver',
		'webSockets.storage.topics.ttl',
		'webSockets.server',
		'webSockets.server.httpHost',
		'webSockets.server.port',
		'webSockets.server.address',
		'webSockets.server.secured',
		'webSockets.server.secured.enable',
		'webSockets.server.secured.sslSettings',
		'webSockets.routes',
		'webSockets.mapping',
		'webSockets.loop',
		'http',
		'http.static',
		'http.static.publicRoot',
		'http.static.enabled',
		'http.server',
		'http.server.address',
		'http.server.port',
		'http.server.certificate',
		'http.cors',
		'http.cors.enabled',
		'http.cors.allow',
		'http.cors.allow.origin',
		'http.cors.allow.methods',
		'http.cors.allow.credentials',
		'http.cors.allow.headers',
		'webSockets.access',
		'webSockets.access.keys',
		'webSockets.access.origins',
	];

	private const array CENSUS_TAG_TARGETS = [
		'fastybird.core.documents.attributeDriver',
		'fastybird.core.exchange.consumerState',
		'fastybird.core.exchange.consumerRoutingKey',
		'fastybird.core.webSockets.routes',
		'fastybird.core.webSockets.controller',
	];

	public function testNoNewAndNoFixedViolations(): void
	{
		$actual = array_merge(
			$this->serviceViolations(),
			self::configurationViolations(),
			self::tagViolations(),
		);
		sort($actual);

		$lines = file(self::VIOLATIONS_FILE, FILE_IGNORE_NEW_LINES);
		self::assertIsArray($lines);

		$expected = array_values(array_filter(
			array_map(static fn (string $line): string => trim($line), $lines),
			static fn (string $line): bool => $line !== '' && !str_starts_with($line, '#'),
		));

		self::assertSame(
			[],
			array_values(array_diff($actual, $expected)),
			'New DI identifiers break a naming rule. Name them for their capability instead.',
		);

		self::assertSame(
			[],
			array_values(array_diff($expected, $actual)),
			sprintf('These no longer occur as listed; remove or correct them in %s.', self::VIOLATIONS_FILE),
		);

		self::assertSame(array_values(array_unique($expected)), $expected, 'Duplicate entries');
	}

	public function testEveryCensusTargetPassesEveryRule(): void
	{
		$failures = [];

		foreach (self::CENSUS_SERVICE_TARGETS as $name) {
			$rules = self::serviceRules($name);

			if ($rules !== []) {
				$failures[] = sprintf('service %s %s', $name, implode(',', $rules));
			}
		}

		foreach (self::CENSUS_KEY_TARGETS as $path) {
			$rules = self::keyRules(explode('.', $path));

			if ($rules !== []) {
				$failures[] = sprintf('config %s %s', $path, implode(',', $rules));
			}
		}

		foreach (self::CENSUS_TAG_TARGETS as $tag) {
			$rules = self::tagRules($tag);

			if ($rules !== []) {
				$failures[] = sprintf('tag %s %s', $tag, implode(',', $rules));
			}
		}

		self::assertSame([], $failures);
	}

	/**
	 * @return list<string>
	 */
	private static function serviceRules(string $name): array
	{
		$segments = explode('.', $name);
		$rules = [];

		if (
			self::denied($segments)
			|| ($segments[0] === CoreExtension::NAME && ($segments[1] ?? null) === self::POSITIONAL_ENTRY)
		) {
			$rules[] = 'denylist';
		}

		$capability = '(?:' . implode('|', self::CAPABILITIES) . ')';
		$rootGroup = '(?:' . implode('|', self::ROOT_GROUPS) . ')';

		if (
			!in_array($name, self::ROOT_SERVICES, true)
			&& preg_match('/^fbCore\.' . $capability . '\.' . self::ROLE . '$/', $name) !== 1
			&& preg_match('/^fbCore\.' . $rootGroup . '\.' . self::ROLE . '$/', $name) !== 1
		) {
			$rules[] = 'pattern';
		}

		return $rules;
	}

	/**
	 * @param list<string> $path
	 *
	 * @return list<string>
	 */
	private static function keyRules(array $path): array
	{
		return self::denied($path) || $path[0] === self::POSITIONAL_ENTRY ? ['denylist'] : [];
	}

	/**
	 * @return list<string>
	 */
	private static function tagRules(string $tag): array
	{
		$rules = [];

		if (self::denied(explode('.', $tag))) {
			$rules[] = 'denylist';
		}

		$capability = '(?:' . implode('|', self::CAPABILITIES) . ')';

		if (preg_match('/^fastybird\.core\.' . $capability . '\.' . self::ROLE . '$/', $tag) !== 1) {
			$rules[] = 'pattern';
		}

		return $rules;
	}

	/**
	 * @return list<string>
	 */
	private function serviceViolations(): array
	{
		$violations = [];

		foreach (array_keys($this->container->getServiceDescriptors()) as $name) {
			$isCore = str_starts_with($name, CoreExtension::NAME . '.');

			foreach (self::UNPREFIXED_SERVICES as $prefix) {
				$isCore = $isCore || str_starts_with($name, $prefix);
			}

			$rules = $isCore ? self::serviceRules($name) : [];

			if ($rules !== []) {
				$violations[] = sprintf('service %s %s', $name, implode(',', $rules));
			}
		}

		return $violations;
	}

	/**
	 * Every node of the schema, sections and leaves, each as its full key path
	 *
	 * @return list<string>
	 */
	private static function configurationViolations(): array
	{
		$violations = [];

		foreach (self::keyPaths((new CoreExtension())->getConfigSchema(), []) as $path) {
			$rules = self::keyRules($path);

			if ($rules !== []) {
				$violations[] = sprintf('config %s %s', implode('.', $path), implode(',', $rules));
			}
		}

		return $violations;
	}

	/**
	 * @return list<string>
	 */
	private static function tagViolations(): array
	{
		$violations = [];

		$constants = (new ReflectionClass(CoreExtension::class))->getReflectionConstants(
			ReflectionClassConstant::IS_PUBLIC,
		);

		foreach ($constants as $constant) {
			$value = $constant->getValue();

			if ($constant->getName() === 'NAME' || !is_string($value)) {
				continue;
			}

			$rules = self::tagRules($value);

			if ($rules !== []) {
				$violations[] = sprintf('tag %s %s', $value, implode(',', $rules));
			}
		}

		return $violations;
	}

	/**
	 * @param list<string> $path
	 *
	 * @return list<list<string>>
	 */
	private static function keyPaths(Schema $schema, array $path): array
	{
		if (!$schema instanceof Structure) {
			return [];
		}

		$paths = [];

		foreach ($schema->getShape() as $key => $item) {
			$itemPath = [...$path, (string) $key];
			$paths[] = $itemPath;
			$paths = array_merge($paths, self::keyPaths($item, $itemPath));
		}

		return $paths;
	}

	/**
	 * @param array<string> $segments
	 */
	private static function denied(array $segments): bool
	{
		$denylist = array_map(static fn (string $entry): string => strtolower($entry), self::DENYLIST);

		foreach ($segments as $segment) {
			if (in_array(strtolower($segment), $denylist, true)) {
				return true;
			}
		}

		return false;
	}

}
