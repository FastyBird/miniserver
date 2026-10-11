<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Entities\Widgets\Displays\Parameters;

/**
 * Display precision parameter interface
 */
interface Precision
{

	public function setPrecision(int $precision): void;

	public function getPrecision(): int;

}
