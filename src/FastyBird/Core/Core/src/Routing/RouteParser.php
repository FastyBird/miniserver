<?php declare(strict_types = 1);

namespace FastyBird\Core\Routing;

use FastRoute\RouteParser\Std;
use FastyBird\Core\Exceptions;
use Psr\Http\Message\UriInterface;
use function array_key_exists;
use function array_reverse;
use function http_build_query;
use function implode;
use function is_string;

class RouteParser implements IRouteParser
{

	private Std $routeParser;

	public function __construct(private IRouter $router)
	{
		$this->routeParser = new Std();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function relativeUrlFor(string $routeName, array $data = [], array $queryParams = []): string
	{
		$route = $this->router->getNamedRoute($routeName);

		if ($route === null) {
			throw new Exceptions\InvalidArgument('Route could not be found in storage');
		}

		$pattern = $route->getPattern();

		$segments = [];
		$segmentName = '';

		/*
		 * $routes is an associative array of expressions representing a route as multiple segments
		 * There is an expression for each optional parameter plus one without the optional parameters
		 * The most specific is last, hence why we reverse the array before iterating over it
		 */
		$expressions = array_reverse($this->routeParser->parse($pattern));

		foreach ($expressions as $expression) {
			foreach ($expression as $segment) {
				/*
				 * Each $segment is either a string or an array of strings
				 * containing optional parameters of an expression
				 */
				if (is_string($segment)) {
					$segments[] = $segment;

					continue;
				}

				/*
				 * If we don't have a data element for this segment in the provided $data
				 * we cancel testing to move onto the next expression with a less specific item
				 */
				if (!array_key_exists($segment[0], $data)) {
					$segments = [];
					$segmentName = $segment[0];

					break;
				}

				$segments[] = $data[$segment[0]];
			}

			/*
			 * If we get to this logic block we have found all the parameters
			 * for the provided $data which means we don't need to continue testing
			 * less specific expressions
			 */
			if ($segments !== []) {
				break;
			}
		}

		if ($segments === []) {
			throw new Exceptions\InvalidArgument('Missing data for URL segment: ' . $segmentName);
		}

		$url = implode('', $segments);

		if ($queryParams !== []) {
			$url .= '?' . http_build_query($queryParams);
		}

		return $url;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function urlFor(string $routeName, array $data = [], array $queryParams = []): string
	{
		$basePath = $this->router->getBasePath();
		$url = $this->relativeUrlFor($routeName, $data, $queryParams);

		if ($basePath !== '') {
			$url = $basePath . $url;
		}

		return $url;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function fullUrlFor(UriInterface $uri, string $routeName, array $data = [], array $queryParams = []): string
	{
		$path = $this->urlFor($routeName, $data, $queryParams);
		$scheme = $uri->getScheme();
		$authority = $uri->getAuthority();
		$protocol = ($scheme !== '' ? $scheme . ':' : '') . ($authority !== '' ? '//' . $authority : '');

		return $protocol . $path;
	}

}
