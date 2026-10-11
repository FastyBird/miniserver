<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Entities\Widgets\Displays\Parameters;

/**
 * Display step value parameter interface
 */
interface StepValue
{

	public function setStepValue(float $stepValue): void;

	public function getStepValue(): float;

}
