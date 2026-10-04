<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Encoding\Objects;

use FastyBird\Core\Api\Encoding;
use FastyBird\Core\Exceptions;
use function is_string;

/**
 * Source object
 */
final class SourceObject
{

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(private StandardObject $data)
	{
		if (
			!$data->has(Encoding\Document::KEYWORD_POINTER)
			&& !$data->has(Encoding\Document::KEYWORD_PARAMETER)
		) {
			throw new Exceptions\InvalidArgument('Provided source object has missing required attribute');
		}
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	public function getPointer(): string|null
	{
		$pointer = $this->data->get(Encoding\Document::KEYWORD_POINTER);

		if (!is_string($pointer)) {
			throw new Exceptions\Runtime('Value of pointer attribute of source object has invalid value.');
		}

		return $pointer;
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	public function getParameter(): string|null
	{
		$parameter = $this->data->get(Encoding\Document::KEYWORD_PARAMETER);

		if (!is_string($parameter)) {
			throw new Exceptions\Runtime('Value of parameter attribute of source object has invalid value.');
		}

		return $parameter;
	}

}
