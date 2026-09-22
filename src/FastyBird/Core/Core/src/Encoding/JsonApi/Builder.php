<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\JsonApi;

use FastyBird\Core\Encoding\JsonApi;
use InvalidArgumentException;
use Neomerx;
use Neomerx\JsonApi\Contracts;
use Neomerx\JsonApi\Schema;
use Nette\DI;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use function array_key_exists;
use function array_merge;
use function call_user_func_array;
use function explode;
use function http_build_query;
use function is_array;
use function round;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strval;
use const JSON_PRETTY_PRINT;

/**
 * {JSON:API} formatting output handling middleware
 */
class Builder
{

	private const string LINK_SELF = Contracts\Schema\DocumentInterface::KEYWORD_SELF;

	private const string LINK_RELATED = Contracts\Schema\DocumentInterface::KEYWORD_RELATED;

	private const string LINK_FIRST = Contracts\Schema\DocumentInterface::KEYWORD_FIRST;

	private const string LINK_LAST = Contracts\Schema\DocumentInterface::KEYWORD_LAST;

	private const string LINK_NEXT = Contracts\Schema\DocumentInterface::KEYWORD_NEXT;

	private const string LINK_PREV = Contracts\Schema\DocumentInterface::KEYWORD_PREV;

	/**
	 * @param string|array<string> $metaAuthor
	 */
	public function __construct(
		private readonly DI\Container $container,
		private readonly string|array $metaAuthor,
		private readonly string|null $metaCopyright = null,
	)
	{
	}

	/**
	 * @param object|array<object>|null $entity
	 * @param callable(string): bool $linkValidator
	 *
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function build(
		ServerRequestInterface $request,
		ResponseInterface $response,
		object|array|null $entity,
		int|null $totalCount = null,
		callable|null $linkValidator = null,
	): ResponseInterface
	{
		$encoder = $this->getEncoder();

		$links = [
			self::LINK_SELF => new Schema\Link(false, $this->uriToString($request->getUri()), false),
		];

		$meta = $this->getBaseMeta();

		if ($totalCount !== null) {
			$meta = array_merge($meta, [
				'totalCount' => $totalCount,
			]);

			if (array_key_exists('page', $request->getQueryParams())) {
				$queryParams = $request->getQueryParams();

				$pageOffset = isset($queryParams['page']['offset']) ? (int) $queryParams['page']['offset'] : null;
				$pageLimit = isset($queryParams['page']['limit']) ? (int) $queryParams['page']['limit'] : null;

			} else {
				$pageOffset = null;
				$pageLimit = null;
			}

			if ($pageOffset !== null && $pageLimit !== null && $pageLimit > 0) {
				$lastPage = (int) round($totalCount / $pageLimit) * $pageLimit;

				if ($lastPage === $totalCount) {
					$lastPage = $totalCount - $pageLimit;
				}

				$uri = $request->getUri();

				$uriSelf = $uri->withQuery($this->buildPageQuery($pageOffset, $pageLimit));
				$uriFirst = $uri->withQuery($this->buildPageQuery(0, $pageLimit));
				$uriLast = $uri->withQuery($this->buildPageQuery($lastPage, $pageLimit));
				$uriPrev = $uri->withQuery($this->buildPageQuery($pageOffset - $pageLimit, $pageLimit));
				$uriNext = $uri->withQuery($this->buildPageQuery($pageOffset + $pageLimit, $pageLimit));

				$links = array_merge($links, [
					self::LINK_SELF => new Schema\Link(false, $this->uriToString($uriSelf), false),
					self::LINK_FIRST => new Schema\Link(false, $this->uriToString($uriFirst), false),
				]);

				if ($pageOffset - 1 >= 0) {
					$links = array_merge($links, [
						self::LINK_PREV => new Schema\Link(false, $this->uriToString($uriPrev), false),
					]);
				}

				if ($totalCount - $pageLimit - ($pageOffset + $pageLimit) >= 0) {
					$links = array_merge($links, [
						self::LINK_NEXT => new Schema\Link(false, $this->uriToString($uriNext), false),
					]);
				}

				$links = array_merge($links, [
					self::LINK_LAST => new Schema\Link(false, $this->uriToString($uriLast), false),
				]);
			}
		}

		$encoder->withMeta($meta);

		$encoder->withLinks($links);

		if (str_contains($request->getUri()->getPath(), '/relationships/')) {
			$encodedData = $encoder->encodeDataAsArray($entity);

			// Try to get "self" link from encoded entity as array
			if (
				array_key_exists('data', $encodedData)
				&& array_key_exists('links', $encodedData['data'])
				&& array_key_exists(self::LINK_SELF, $encodedData['data']['links'])
			) {
				$encoder->withLinks(array_merge($links, [
					self::LINK_RELATED => new Schema\Link(
						false,
						strval($encodedData['data']['links'][self::LINK_SELF]),
						false,
					),
				]));

			} else {
				if ($linkValidator !== null) {
					$uriRelated = $request->getUri();

					$linkRelated = str_replace('/relationships/', '/', $this->uriToString($uriRelated));

					$isValid = call_user_func_array($linkValidator, [$linkRelated]);

					if ($isValid === true) {
						$encoder->withLinks(array_merge($links, [
							self::LINK_RELATED => new Schema\Link(false, $linkRelated, false),
						]));
					}
				}
			}

			$content = $encoder->encodeIdentifiers($entity);

		} else {
			if (array_key_exists('include', $request->getQueryParams())) {
				$encoder->withIncludedPaths(explode(',', $request->getQueryParams()['include']));
			}

			$content = $encoder->encodeData($entity);
		}

		$response->getBody()->write($content);

		// Setup content type
		return $response
			// Content headers
			->withHeader('Content-Type', Contracts\Http\Headers\MediaTypeInterface::JSON_API_MEDIA_TYPE);
	}

	/**
	 * @throws DI\MissingServiceException
	 */
	private function getEncoder(): JsonApi\Encoder
	{
		$encoder = new JsonApi\Encoder(
			new Neomerx\JsonApi\Factories\Factory(),
			$this->container->getByType(Contracts\Schema\SchemaContainerInterface::class),
		);

		$encoder->withEncodeOptions(JSON_PRETTY_PRINT);

		$encoder->withJsonApiVersion(Contracts\Encoder\EncoderInterface::JSON_API_VERSION);

		return $encoder;
	}

	private function uriToString(UriInterface $uri): string
	{
		$result = '';

		// Add a leading slash if necessary.
		if (!str_starts_with($uri->getPath(), '/')) {
			$result .= '/';
		}

		$result .= $uri->getPath();

		if ($uri->getQuery() !== '') {
			$result .= '?' . $uri->getQuery();
		}

		if ($uri->getFragment() !== '') {
			$result .= '#' . $uri->getFragment();
		}

		return $result;
	}

	/**
	 * @return array<mixed>
	 */
	private function getBaseMeta(): array
	{
		$meta = [];

		if (is_array($this->metaAuthor)) {
			$meta['authors'] = $this->metaAuthor;

		} else {
			$meta['author'] = $this->metaAuthor;
		}

		if ($this->metaCopyright !== null) {
			$meta['copyright'] = $this->metaCopyright;
		}

		return $meta;
	}

	private function buildPageQuery(int $offset, int|string $limit): string
	{
		$query = [
			'page' => [
				'offset' => $offset,
				'limit' => $limit,
			],
		];

		return http_build_query($query);
	}

}
