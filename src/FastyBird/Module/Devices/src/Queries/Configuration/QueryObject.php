<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Queries\Configuration;

use FastyBird\Core\Documents;
use Flow\JSONPath;
use function bin2hex;
use function preg_replace_callback;

/**
 * Configuration query object
 *
 * @template T of Documents\Document
 */
abstract class QueryObject
{

	public function fetch(JSONPath\JSONPath $repository): mixed
	{
		return $this->doCreateQuery($repository)->getData();
	}

	abstract protected function doCreateQuery(JSONPath\JSONPath $repository): JSONPath\JSONPath;

	abstract public function toString(): string;

	/**
	 * The value as a pattern that matches only the value itself. Every character but a letter, a digit, `-` and `_` is
	 * written as its byte value, so none of them can mean anything to the pattern, nor open or close a bracket of the
	 * path expression the pattern sits in.
	 */
	protected static function literal(string $value): string
	{
		return (string) preg_replace_callback(
			'/[^A-Za-z0-9\-_]/',
			static fn (array $match): string => '\\x' . bin2hex($match[0]),
			$value,
		);
	}

}
