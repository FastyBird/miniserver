<?php declare(strict_types = 1);

/**
 * SourceObject.php
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

namespace FastyBird\Core\Encoding\JsonApi\Objects;

use FastyBird\Core\Encoding\JsonApi;
use FastyBird\Core\Encoding\JsonApi\Objects;
use FastyBird\Core\Exceptions;
use function is_string;

/**
 * Source object
 *
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
class SourceObject implements ISourceObject
{

	public function __construct(private Objects\IStandardObject $data)
	{
		if (
			!$data->has(JsonApi\IDocument::KEYWORD_POINTER)
			&& !$data->has(JsonApi\IDocument::KEYWORD_PARAMETER)
		) {
			throw new Exceptions\InvalidArgument('Provided source object has missing required attribute');
		}
	}

	public function getPointer(): string|null
	{
		$pointer = $this->data->get(JsonApi\IDocument::KEYWORD_POINTER);

		if (!is_string($pointer)) {
			throw new Exceptions\Runtime('Value of pointer attribute of source object has invalid value.');
		}

		return $pointer;
	}

	public function getParameter(): string|null
	{
		$parameter = $this->data->get(JsonApi\IDocument::KEYWORD_PARAMETER);

		if (!is_string($parameter)) {
			throw new Exceptions\Runtime('Value of parameter attribute of source object has invalid value.');
		}

		return $parameter;
	}

}
