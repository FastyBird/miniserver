<?php declare(strict_types = 1);

/**
 * LinkObject.php
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
use function is_string;

/**
 * Link object
 *
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
class LinkObject implements ILinkObject
{

	public function __construct(private Objects\IStandardObject $data)
	{
		if (!$data->has(JsonApi\IDocument::KEYWORD_HREF)) {
			throw new Exceptions\InvalidArgument('Provided link object has missing required attribute');
		}
	}

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

	public function getMeta(): IMetaObjectCollection
	{
		$raw = $this->data->get(JsonApi\IDocument::KEYWORD_META);

		if (!$raw instanceof Objects\IStandardObject && $raw !== null) {
			throw new Exceptions\Runtime('Meta member is not an object.');
		}

		return MetaObjectCollection::create($raw);
	}

}
