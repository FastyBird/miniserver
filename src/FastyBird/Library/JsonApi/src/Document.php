<?php declare(strict_types = 1);

/**
 * Document.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     common
 * @since          0.0.1
 *
 * @date           05.05.18
 */

namespace FastyBird\Library\JsonApi;

use FastyBird\Library\JsonApi\Exceptions\InvalidArgument;
use FastyBird\Library\JsonApi\Objects\IStandardObject;
use JsonException;
use stdClass;
use function is_array;
use function json_decode;

/**
 * JSON:API document
 *
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
class Document implements IDocument
{

	private Objects\IStandardObject $data;

	public function __construct(stdClass $data)
	{
		$this->data = new Objects\StandardObject($data);
	}

	public static function create(string|stdClass $data): IDocument
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

	public function hasResource(): bool
	{
		$data = $this->getData();

		return $data instanceof IStandardObject;
	}

	public function getResource(): Objects\IResourceObject
	{
		$data = $this->getData();

		if (!$data instanceof Objects\IStandardObject) {
			throw new Exceptions\Runtime('Data member is not an object.');
		}

		return new Objects\ResourceObject($data);
	}

	public function hasResources(): bool
	{
		$data = $this->getData();

		return $data instanceof Objects\IStandardObjectCollection;
	}

	public function getResources(): Objects\IResourceObjectCollection
	{
		$data = $this->getData();

		if (!$data instanceof Objects\IStandardObjectCollection) {
			throw new Exceptions\Runtime('Data member is not an array.');
		}

		return Objects\ResourceObjectCollection::create($data->getAll());
	}

	public function getData(): Objects\IStandardObject|Objects\IStandardObjectCollection|null
	{
		if (!$this->data->has(self::KEYWORD_DATA)) {
			throw new Exceptions\Runtime('Data member is not present.');
		}

		$data = $this->data->get(self::KEYWORD_DATA);

		if (is_array($data)) {
			return Objects\StandardObjectCollection::create($data);
		}

		if (!$data instanceof Objects\IStandardObject && $data !== null) {
			throw new Exceptions\Runtime('Data member is not an object or null.');
		}

		return $data;
	}

	public function hasLinks(): bool
	{
		return $this->data->has(self::KEYWORD_LINKS);
	}

	public function getLinks(): Objects\ILinkObjectCollection
	{
		$raw = $this->data->get(self::KEYWORD_LINKS);

		if (!$raw instanceof Objects\IStandardObject && $raw !== null) {
			throw new Exceptions\Runtime('Links member is not an object.');
		}

		return Objects\LinkObjectCollection::create($raw);
	}

	public function hasMeta(): bool
	{
		return $this->data->has(self::KEYWORD_META);
	}

	public function getMeta(): Objects\IMetaObjectCollection
	{
		$raw = $this->data->get(self::KEYWORD_META);

		if (!$raw instanceof Objects\IStandardObject && $raw !== null) {
			throw new Exceptions\Runtime('Meta member is not an object.');
		}

		return Objects\MetaObjectCollection::create($raw);
	}

	public function hasIncluded(): bool
	{
		return $this->data->has(self::KEYWORD_INCLUDED);
	}

	public function getIncluded(): Objects\IResourceObjectCollection
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

	public function getErrors(): Objects\IErrorObjectCollection
	{
		$raw = $this->data->get(self::KEYWORD_ERRORS);

		if (!is_array($raw)) {
			throw new Exceptions\Runtime('Errors member is not an array.');
		}

		return Objects\ErrorObjectCollection::create(Objects\StandardObjectCollection::create($raw)
			->getAll());
	}

}
