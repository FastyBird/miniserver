<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\JsonApi\Objects;

use FastyBird\Core\Encoding\JsonApi;
use FastyBird\Core\Encoding\JsonApi\Objects;
use FastyBird\Core\Exceptions;
use function is_string;

/**
 * Link object
 */
class LinkObject implements ILinkObject
{

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(private Objects\IStandardObject $data)
	{
		if (!$data->has(JsonApi\IDocument::KEYWORD_HREF)) {
			throw new Exceptions\InvalidArgument('Provided link object has missing required attribute');
		}
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	public function getHref(): string
	{
		$href = $this->data->get(JsonApi\IDocument::KEYWORD_HREF);

		if (!is_string($href)) {
			throw new Exceptions\Runtime('Value of href attribute of link object has invalid value.');
		}

		return $href;
	}

	public function hasMeta(): bool
	{
		return $this->data->has(JsonApi\IDocument::KEYWORD_META);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	public function getMeta(): IMetaObjectCollection
	{
		$raw = $this->data->get(JsonApi\IDocument::KEYWORD_META);

		if (!$raw instanceof Objects\IStandardObject && $raw !== null) {
			throw new Exceptions\Runtime('Meta member is not an object.');
		}

		return MetaObjectCollection::create($raw);
	}

}
