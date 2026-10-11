<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Entities\Widgets\Displays\Parameters;

/**
 * Display maximum value parameter interface
 */
interface MaximumValue
{

	public function setMaximumValue(float $maximumValue): void;

	public function getMaximumValue(): float|null;

}
