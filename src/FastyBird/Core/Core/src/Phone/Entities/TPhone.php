<?php declare(strict_types = 1);

namespace FastyBird\Core\Phone\Entities;

use FastyBird\Core\Phone\Services;

/**
 * Phone number helpers trait
 */
trait TPhone
{

	protected Services\PhoneNumberHelper $phone;

	public function injectPhone(Services\PhoneNumberHelper $phone): void
	{
		$this->phone = $phone;
	}

}
