<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Queries\Configuration;

use FastyBird\Core\Documents;
use Flow\JSONPath;

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

}
