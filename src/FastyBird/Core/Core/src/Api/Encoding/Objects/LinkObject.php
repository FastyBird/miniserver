<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Encoding\Objects;

use FastyBird\Core\Api\Encoding;
use FastyBird\Core\Exceptions;
use function is_string;

/**
 * Link object
 */
final class LinkObject
{

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(private StandardObject $data)
	{
		if (!$data->has(Encoding\Document::KEYWORD_HREF)) {
			throw new Exceptions\InvalidArgument('Provided link object has missing required attribute');
		}
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	public function getHref(): string
	{
		$href = $this->data->get(Encoding\Document::KEYWORD_HREF);

		if (!is_string($href)) {
			throw new Exceptions\Runtime('Value of href attribute of link object has invalid value.');
		}

		return $href;
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
