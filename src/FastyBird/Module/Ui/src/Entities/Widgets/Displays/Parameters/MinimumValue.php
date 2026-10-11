<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Entities\Widgets\Displays\Parameters;

/**
 * Display minimum value parameter interface
 */
interface MinimumValue
{

	public function setMinimumValue(float $minimumValue): void;

	public function getMinimumValue(): float|null;

}
