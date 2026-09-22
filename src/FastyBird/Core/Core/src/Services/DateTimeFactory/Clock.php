<?php declare(strict_types = 1);

namespace FastyBird\Core\Services\DateTimeFactory;

use DateTimeInterface;

interface Clock
{

	public function getNow(): DateTimeInterface;

}
