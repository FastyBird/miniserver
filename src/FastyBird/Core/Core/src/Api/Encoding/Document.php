<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Encoding;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions\InvalidArgument;
use JsonException;
use stdClass;
use function is_array;
use function json_decode;

/**
 * JSON:API document
 */
final class Document
{

	// Reserved keyword
	public const string KEYWORD_LINKS = 'links';

	// Reserved keyword
	public const string KEYWORD_HREF = 'href';

	// Reserved keyword
	public const string KEYWORD_RELATIONSHIPS = 'relationships';

	// Reserved keyword
	public const string KEYWORD_SELF = 'self';

	// Reserved keyword
	public const string KEYWORD_FIRST = 'first';

	// Reserved keyword
	public const string KEYWORD_LAST = 'last';

	// Reserved keyword
	public const string KEYWORD_NEXT = 'next';

	// Reserved keyword
	public const string KEYWORD_PREV = 'prev';

	// Reserved keyword
	public const string KEYWORD_RELATED = 'related';

	// Reserved keyword
	public const string KEYWORD_TYPE = 'type';

	// Reserved keyword
	public const string KEYWORD_ID = 'id';

	// Reserved keyword
	public const string KEYWORD_ATTRIBUTES = 'attributes';

	// Reserved keyword
	public const string KEYWORD_META = 'meta';

	// Reserved keyword
	public const string KEYWORD_ALIASES = 'aliases';

	// Reserved keyword
	public const string KEYWORD_PROFILE = 'profile';

	// Reserved keyword
	public const string KEYWORD_DATA = 'data';

	// Reserved keyword
	public const string KEYWORD_INCLUDED = 'included';

	// Reserved keyword
	public const string KEYWORD_JSON_API = 'jsonapi';

	// Reserved keyword
	public const string KEYWORD_VERSION = 'version';

	// Reserved keyword
	public const string KEYWORD_ERRORS = 'errors';

	// Reserved keyword
	public const string KEYWORD_ERRORS_ID = 'id';

	// Reserved keyword
	public const string KEYWORD_ERRORS_TYPE = 'type';

	// Reserved keyword
	public const string KEYWORD_ERRORS_STATUS = 'status';

	// Reserved keyword
	public const string KEYWORD_ERRORS_CODE = 'code';

	// Reserved keyword
	public const string KEYWORD_ERRORS_TITLE = 'title';

	// Reserved keyword
	public const string KEYWORD_ERRORS_DETAIL = 'detail';

	// Reserved keyword
	public const string KEYWORD_ERRORS_META = 'meta';

	// Reserved keyword
	public const string KEYWORD_ERRORS_SOURCE = 'source';

	// Reserved keyword
	public const string KEYWORD_ERRORS_ABOUT = 'about';

	// Reserved keyword
	public const string KEYWORD_POINTER = 'pointer';

	// Reserved keyword
	public const string KEYWORD_PARAMETER = 'parameter';

	// Include path separator
	public const string PATH_SEPARATOR = '.';

	private Objects\StandardObject $data;

	public function __construct(stdClass $data)
	{
		$this->data = new Objects\StandardObject($data);
	}

	/**
	 * @throws InvalidArgument
	 */
	public static function create(string|stdClass $data): Document
	{
		if ($data instanceof stdClass) {
			return new self($data);
		}

		try {
			return new self(json_decode($data));
		} catch (JsonException) {
			throw new InvalidArgument('Provided data are not valid string or object');
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	public function hasResource(): bool
	{
		$data = $this->getData();

		return $data instanceof Objects\StandardObject;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	public function getResource(): Objects\ResourceObject
	{
		$data = $this->getData();

		if (!$data instanceof Objects\StandardObject) {
			throw new Exceptions\Runtime('Data member is not an object.');
		}

		return new Objects\ResourceObject($data);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	public function hasResources(): bool
	{
		$data = $this->getData();

		return $data instanceof Objects\StandardObjectCollection;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	public function getResources(): Objects\ResourceObjectCollection
	{
		$data = $this->getData();

		if (!$data instanceof Objects\StandardObjectCollection) {
			throw new Exceptions\Runtime('Data member is not an array.');
		}

		return Objects\ResourceObjectCollection::create($data->getAll());
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	public function getData(): Objects\StandardObject|Objects\StandardObjectCollection|null
	{
		if (!$this->data->has(self::KEYWORD_DATA)) {
			throw new Exceptions\Runtime('Data member is not present.');
		}

		$data = $this->data->get(self::KEYWORD_DATA);

		if (is_array($data)) {
			return Objects\StandardObjectCollection::create($data);
		}

		if (!$data instanceof Objects\StandardObject && $data !== null) {
			throw new Exceptions\Runtime('Data member is not an object or null.');
		}

		return $data;
	}

	public function hasLinks(): bool
	{
		return $this->data->has(self::KEYWORD_LINKS);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	public function getLinks(): Objects\LinkObjectCollection
	{
		$raw = $this->data->get(self::KEYWORD_LINKS);

		if (!$raw instanceof Objects\StandardObject && $raw !== null) {
			throw new Exceptions\Runtime('Links member is not an object.');
		}

		return Objects\LinkObjectCollection::create($raw);
	}

	public function hasMeta(): bool
	{
		return $this->data->has(self::KEYWORD_META);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	public function getMeta(): Objects\MetaObjectCollection
	{
		$raw = $this->data->get(self::KEYWORD_META);

		if (!$raw instanceof Objects\StandardObject && $raw !== null) {
			throw new Exceptions\Runtime('Meta member is not an object.');
		}

		return Objects\MetaObjectCollection::create($raw);
	}

	public function hasIncluded(): bool
	{
		return $this->data->has(self::KEYWORD_INCLUDED);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	public function getIncluded(): Objects\ResourceObjectCollection
	{
		$raw = $this->data->get(self::KEYWORD_INCLUDED);

		if (!is_array($raw)) {
			throw new Exceptions\Runtime('Included member is not an array.');
		}

		return Objects\ResourceObjectCollection::create(Objects\StandardObjectCollection::create($raw)->getAll());
	}

	public function hasErrors(): bool
	{
		return $this->data->has(self::KEYWORD_ERRORS);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	public function getErrors(): Objects\ErrorObjectCollection
	{
		$raw = $this->data->get(self::KEYWORD_ERRORS);

		if (!is_array($raw)) {
			throw new Exceptions\Runtime('Errors member is not an array.');
		}

		return Objects\ErrorObjectCollection::create(Objects\StandardObjectCollection::create($raw)
			->getAll());
	}

}
