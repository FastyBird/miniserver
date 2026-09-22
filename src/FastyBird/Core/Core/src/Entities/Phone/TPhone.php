<?php declare(strict_types = 1);

namespace FastyBird\Core\Entities\Phone;

use FastyBird\Core\Services\Phone\Phone;

/**
 * Phone number helpers trait
 */
trait TPhone
{

	protected Phone $phone;

	public function injectPhone(Phone $phone): void
	{
		$this->phone = $phone;
	}

}
