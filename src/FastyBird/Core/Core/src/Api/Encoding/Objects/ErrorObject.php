<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Encoding\Objects;

use FastyBird\Core\Api\Encoding;
use FastyBird\Core\Exceptions;
use function is_numeric;
use function is_string;

/**
 * Error object
 */
final class ErrorObject
{

	public function __construct(private StandardObject $data)
	{
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	public function getId(): string|null
	{
		$raw = $this->data->get(Encoding\Document::KEYWORD_ERRORS_ID);

		if (!is_string($raw) && $raw !== null) {
			throw new Exceptions\Runtime('Value of id attribute of error object has invalid value.');
		}

		return $raw;
	}

	public function hasLinks(): bool
	{
		return $this->data->has(Encoding\Document::KEYWORD_LINKS);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	public function getLinks(): LinkObjectCollection
	{
		$raw = $this->data->get(Encoding\Document::KEYWORD_LINKS);

		if (!$raw instanceof StandardObject && $raw !== null) {
			throw new Exceptions\Runtime('Links member is not an object.');
		}

		return LinkObjectCollection::create($raw);
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	public function getStatus(): int|null
	{
		$raw = $this->data->get(Encoding\Document::KEYWORD_ERRORS_STATUS);

		if (!is_numeric($raw) && $raw !== null) {
			throw new Exceptions\Runtime('Value of status attribute of error object has invalid value.');
		}

		return $raw !== null ? (int) $raw : null;
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	public function getCode(): string|null
	{
		$raw = $this->data->get(Encoding\Document::KEYWORD_ERRORS_CODE);

		if (!is_string($raw) && $raw !== null) {
			throw new Exceptions\Runtime('Value of code attribute of error object has invalid value.');
		}

		return $raw;
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	public function getTitle(): string|null
	{
		$raw = $this->data->get(Encoding\Document::KEYWORD_ERRORS_TITLE);

		if (!is_string($raw) && $raw !== null) {
			throw new Exceptions\Runtime('Value of title attribute of error object has invalid value.');
		}

		return $raw;
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	public function getDetail(): string|null
	{
		$raw = $this->data->get(Encoding\Document::KEYWORD_ERRORS_DETAIL);

		if (!is_string($raw) && $raw !== null) {
			throw new Exceptions\Runtime('Value of detail attribute of error object has invalid value.');
		}

		return $raw;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	public function getSource(): SourceObject|null
	{
		$raw = $this->data->get(Encoding\Document::KEYWORD_ERRORS_SOURCE);

		if (!$raw instanceof StandardObject && $raw !== null) {
			throw new Exceptions\Runtime('Meta member is not an object.');
		}

		return $raw !== null ? new SourceObject($raw) : null;
	}

	public function hasMeta(): bool
	{
		return $this->data->has(Encoding\Document::KEYWORD_META);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	public function getMeta(): MetaObjectCollection
	{
		$raw = $this->data->get(Encoding\Document::KEYWORD_META);

		if (!$raw instanceof StandardObject && $raw !== null) {
			throw new Exceptions\Runtime('Meta member is not an object.');
		}

		return MetaObjectCollection::create($raw);
	}

}
