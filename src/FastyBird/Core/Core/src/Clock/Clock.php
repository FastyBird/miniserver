<?php declare(strict_types = 1);

namespace FastyBird\Core\Clock;

use DateTimeInterface;

interface Clock
{

	public function getNow(): DateTimeInterface;

}
