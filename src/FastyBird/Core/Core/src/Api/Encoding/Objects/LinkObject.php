<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Encoding\Objects;

use FastyBird\Core\Api\Encoding;
use FastyBird\Core\Exceptions;
use Override;
use function is_string;

/**
 * Link object
 */
final class LinkObject implements ILinkObject
{

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(private IStandardObject $data)
	{
		if (!$data->has(Encoding\IDocument::KEYWORD_HREF)) {
			throw new Exceptions\InvalidArgument('Provided link object has missing required attribute');
		}
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function getHref(): string
	{
		$href = $this->data->get(Encoding\IDocument::KEYWORD_HREF);

		if (!is_string($href)) {
			throw new Exceptions\Runtime('Value of href attribute of link object has invalid value.');
		}

		return $href;
	}

	#[Override]
	public function hasMeta(): bool
	{
		return $this->data->has(Encoding\IDocument::KEYWORD_META);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function getMeta(): IMetaObjectCollection
	{
		$raw = $this->data->get(Encoding\IDocument::KEYWORD_META);

		if (!$raw instanceof IStandardObject && $raw !== null) {
			throw new Exceptions\Runtime('Meta member is not an object.');
		}

		return MetaObjectCollection::create($raw);
	}

}
