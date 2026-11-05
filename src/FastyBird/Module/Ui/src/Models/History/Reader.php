<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Models\History;

use DateTimeInterface;
use FastyBird\Module\Ui\Documents;
use FastyBird\Module\Ui\Exceptions;

/**
 * Reads the history of what a data source shows
 *
 * The Ui module keeps no history and cannot import the module that does, so a package that knows what a data
 * source is bound to implements this: the Devices module bridge, for the data sources of a property.
 */
interface Reader
{

	/**
	 * The history of the data source over the period, in at most $points points, or null when this kind of data
	 * source has none: a generic one, one bound to a property that does not exist or has no value over time
	 *
	 * @throws Exceptions\InvalidState when the history could not be read
	 */
	public function read(
		Documents\Widgets\DataSources\DataSource $dataSource,
		DateTimeInterface $from,
		DateTimeInterface $to,
		int $points,
	): Series|null;

}
