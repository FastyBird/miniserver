<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Queries\Configuration;

use FastyBird\Module\Ui\Documents;
use FastyBird\Module\Ui\Exceptions;
use Flow\JSONPath;
use Nette\Utils;
use Override;
use Ramsey\Uuid;
use function serialize;

/**
 * Find widgets displays configuration query
 *
 * @template T of Documents\Widgets\Displays\Display
 * @extends  QueryObject<T>
 */
final class FindWidgetDisplays extends QueryObject
{

	/** @var array<string> */
	protected array $filter = [];

	public function __construct()
	{
		$this->filter[] = '.[?(@.widget != "")]';
	}

	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = '.[?(@.id =~ /(?i).*^' . $id->toString() . '*$/)]';
	}

	public function forWidget(Documents\Widgets\Widget $widget): void
	{
		$this->filter[] = '.[?(@.widget =~ /(?i).*^' . $widget->getId()->toString() . '*$/)]';
	}

	public function byWidgetId(Uuid\UuidInterface $widgetId): void
	{
		$this->filter[] = '.[?(@.widget =~ /(?i).*^' . $widgetId->toString() . '*$/)]';
	}

	/**
	 * @throws JSONPath\JSONPathException
	 */
	#[Override]
	protected function doCreateQuery(JSONPath\JSONPath $repository): JSONPath\JSONPath
	{
		$filtered = $repository;

		foreach ($this->filter as $filter) {
			$filtered = $filtered->find($filter);
		}

		return $filtered;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	#[Override]
	public function toString(): string
	{
		try {
			return serialize(Utils\Json::encode($this->filter));
		} catch (Utils\JsonException) {
			throw new Exceptions\InvalidState('Cache key could not be generated');
		}
	}

}
