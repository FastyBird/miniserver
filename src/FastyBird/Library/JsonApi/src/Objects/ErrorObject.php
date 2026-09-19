<?php declare(strict_types = 1);

/**
 * ErrorObject.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 * @since          0.2.0
 *
 * @date           19.05.21
 */

namespace FastyBird\Library\JsonApi\Objects;

use FastyBird\Library\JsonApi;
use FastyBird\Library\JsonApi\Exceptions;
use FastyBird\Library\JsonApi\Objects;
use function is_numeric;
use function is_string;

/**
 * Error object
 *
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
class ErrorObject implements IErrorObject
{

	public function __construct(private Objects\IStandardObject $data)
	{
	}

	public function getId(): string|null
	{
		$raw = $this->data->get(JsonApi\IDocument::KEYWORD_ERRORS_ID);

		if (!is_string($raw) && $raw !== null) {
			throw new Exceptions\Runtime('Value of id attribute of error object has invalid value.');
		}

		return $raw;
	}

	public function hasLinks(): bool
	{
		return $this->data->has(JsonApi\IDocument::KEYWORD_LINKS);
	}

	public function getLinks(): ILinkObjectCollection
	{
		$raw = $this->data->get(JsonApi\IDocument::KEYWORD_LINKS);

		if (!$raw instanceof Objects\IStandardObject && $raw !== null) {
			throw new Exceptions\Runtime('Links member is not an object.');
		}

		return LinkObjectCollection::create($raw);
	}

	public function getStatus(): int|null
	{
		$raw = $this->data->get(JsonApi\IDocument::KEYWORD_ERRORS_STATUS);

		if (!is_numeric($raw) && $raw !== null) {
			throw new Exceptions\Runtime('Value of status attribute of error object has invalid value.');
		}

		return $raw !== null ? (int) $raw : null;
	}

	public function getCode(): string|null
	{
		$raw = $this->data->get(JsonApi\IDocument::KEYWORD_ERRORS_CODE);

		if (!is_string($raw) && $raw !== null) {
			throw new Exceptions\Runtime('Value of code attribute of error object has invalid value.');
		}

		return $raw;
	}

	public function getTitle(): string|null
	{
		$raw = $this->data->get(JsonApi\IDocument::KEYWORD_ERRORS_TITLE);

		if (!is_string($raw) && $raw !== null) {
			throw new Exceptions\Runtime('Value of title attribute of error object has invalid value.');
		}

		return $raw;
	}

	public function getDetail(): string|null
	{
		$raw = $this->data->get(JsonApi\IDocument::KEYWORD_ERRORS_DETAIL);

		if (!is_string($raw) && $raw !== null) {
			throw new Exceptions\Runtime('Value of detail attribute of error object has invalid value.');
		}

		return $raw;
	}

	public function getSource(): ISourceObject|null
	{
		$raw = $this->data->get(JsonApi\IDocument::KEYWORD_ERRORS_SOURCE);

		if (!$raw instanceof Objects\IStandardObject && $raw !== null) {
			throw new Exceptions\Runtime('Meta member is not an object.');
		}

		return $raw !== null ? new SourceObject($raw) : null;
	}

	public function hasMeta(): bool
	{
		return $this->data->has(JsonApi\IDocument::KEYWORD_META);
	}

	public function getMeta(): IMetaObjectCollection
	{
		$raw = $this->data->get(JsonApi\IDocument::KEYWORD_META);

		if (!$raw instanceof Objects\IStandardObject && $raw !== null) {
			throw new Exceptions\Runtime('Meta member is not an object.');
		}

		return MetaObjectCollection::create($raw);
	}

}
