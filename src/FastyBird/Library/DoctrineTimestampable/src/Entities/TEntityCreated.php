<?php declare(strict_types = 1);

/**
 * TEntityCreated.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:DoctrineTimestampable!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           06.01.15
 */

namespace FastyBird\Library\DoctrineTimestampable\Entities;

use DateTimeInterface;
use FastyBird\Library\DoctrineTimestampable\Mapping\Annotation as IPub;

/**
 * Doctrine timestampable creating entity
 *
 * @package        iPublikuj:DoctrineTimestampable!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
trait TEntityCreated
{

	/** @IPub\Timestampable(on="create") */
	protected DateTimeInterface|null $createdAt = null;

	public function getCreatedAt(): DateTimeInterface|null
	{
		return $this->createdAt;
	}

	public function setCreatedAt(DateTimeInterface $createdAt): void
	{
		$this->createdAt = $createdAt;
	}

}
