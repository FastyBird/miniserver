<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Encoding\Objects;

use FastyBird\Core\Api\Encoding;
use FastyBird\Core\Exceptions;
use Override;
use function is_string;

/**
 * Source object
 */
final class SourceObject implements ISourceObject
{

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(private IStandardObject $data)
	{
		if (
			!$data->has(Encoding\IDocument::KEYWORD_POINTER)
			&& !$data->has(Encoding\IDocument::KEYWORD_PARAMETER)
		) {
			throw new Exceptions\InvalidArgument('Provided source object has missing required attribute');
		}
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function getPointer(): string|null
	{
		$pointer = $this->data->get(Encoding\IDocument::KEYWORD_POINTER);

		if (!is_string($pointer)) {
			throw new Exceptions\Runtime('Value of pointer attribute of source object has invalid value.');
		}

		return $pointer;
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function getParameter(): string|null
	{
		$parameter = $this->data->get(Encoding\IDocument::KEYWORD_PARAMETER);

		if (!is_string($parameter)) {
			throw new Exceptions\Runtime('Value of parameter attribute of source object has invalid value.');
		}

		return $parameter;
	}

}
