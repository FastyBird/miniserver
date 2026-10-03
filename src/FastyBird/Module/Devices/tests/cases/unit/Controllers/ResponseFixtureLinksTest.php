<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Controllers;

use Error;
use FastRoute;
use FastRoute\RouteCollector as FastRouteRouteCollector;
use FastRoute\RouteParser\Std;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Http\Routing;
use FastyBird\Module\Devices\Tests;
use Fig\Http\Message\RequestMethodInterface;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use RuntimeException;
use function array_key_exists;
use function basename;
use function count;
use function implode;
use function is_array;
use function is_string;
use function parse_url;
use function sprintf;
use function str_starts_with;
use const PHP_EOL;
use const PHP_URL_PATH;

/**
 * Every link in every Devices controller response fixture must resolve to a registered route
 * (#628).
 *
 * Fixtures are the pinned expected output, so a link that resolves nowhere here is a link the
 * API serves that answers 404. The removed Devices UrlFormat middleware produced such links,
 * for example /connectors/{connector}/devices/{device}/channels, and the fixtures pinned them.
 *
 * Every string value of every "links" object is checked, and the "href" of every link object:
 * the document's own links, each resource's self link, each relationship's self and related
 * links, the same for included resources, and the paging links.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class ResponseFixtureLinksTest extends Tests\Cases\Unit\DbTestCase
{

	private const string FIXTURES_DIR = __DIR__ . '/../../../fixtures/Controllers/responses';

	/**
	 * The fixture that used to pin links to routes that do not exist. Its presence keeps the
	 * "every link" assertion from passing vacuously over a walk that came back short.
	 */
	private const string REPRESENTATIVE_FIXTURE = 'devices.create.connector.json';

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	public function testEveryFixtureLinkResolvesToRoute(): void
	{
		$router = $this->getContainer()->getByType(Routing\IRouter::class);

		$dispatcher = $this->createDispatcher($router);

		$checked = 0;
		$perFixture = [];
		$unresolved = [];

		foreach (Utils\Finder::findFiles('*.json')->from(self::FIXTURES_DIR) as $file) {
			$fixture = $file->getPathname();

			$content = Utils\FileSystem::read($fixture);

			if ($content === '') {
				continue;
			}

			$links = [];

			$this->collectLinks(Utils\Json::decode($content, forceArrays: true), $links);

			$perFixture[basename($fixture)] = count($links);

			foreach ($links as $link) {
				$checked++;

				$path = parse_url($link, PHP_URL_PATH);

				$result = is_string($path) && str_starts_with($link, '/')
					? $dispatcher->dispatch(RequestMethodInterface::METHOD_GET, $path)
					: [FastRoute\Dispatcher::NOT_FOUND];

				if ($result[0] !== FastRoute\Dispatcher::FOUND) {
					$unresolved[] = sprintf('%s: %s', basename($fixture), $link);
				}
			}
		}

		self::assertTrue(
			array_key_exists(self::REPRESENTATIVE_FIXTURE, $perFixture),
			sprintf('Fixture %s was not walked', self::REPRESENTATIVE_FIXTURE),
		);
		self::assertGreaterThan(0, $perFixture[self::REPRESENTATIVE_FIXTURE]);
		self::assertGreaterThan(0, $checked);

		self::assertSame(
			[],
			$unresolved,
			sprintf(
				'%d of %d fixture links resolve to no registered route:%s%s',
				count($unresolved),
				$checked,
				PHP_EOL,
				implode(PHP_EOL, $unresolved),
			),
		);
	}

	/**
	 * Same route table the router dispatches with, see Routing\RouteHandler::getDispatcher()
	 */
	private function createDispatcher(Routing\IRouter $router): FastRoute\Dispatcher
	{
		return FastRoute\simpleDispatcher(
			static function (FastRouteRouteCollector $collector) use ($router): void {
				foreach ($router->getIterator() as $route) {
					$collector->addRoute(
						$route->getMethods(),
						$router->getBasePath() . $route->getPattern(),
						$route->getIdentifier(),
					);
				}
			},
			[
				'dispatcher' => Routing\FastRouteDispatcher::class,
				'routeParser' => new Std(),
			],
		);
	}

	/**
	 * @param array<string> $links
	 */
	private function collectLinks(mixed $node, array &$links): void
	{
		if (!is_array($node)) {
			return;
		}

		foreach ($node as $key => $value) {
			if ($key === 'links' && is_array($value)) {
				foreach ($value as $link) {
					if (is_string($link)) {
						$links[] = $link;

					} elseif (is_array($link) && is_string($link['href'] ?? null)) {
						$links[] = $link['href'];
					}
				}
			}

			$this->collectLinks($value, $links);
		}
	}

}
