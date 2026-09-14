<?php declare(strict_types = 1);

/**
 * IEntityCreated.php
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

/**
 * Doctrine timestampable creating entity interface
 *
 * @package        iPublikuj:DoctrineTimestampable!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IEntityCreated
{

	public function setCreatedAt(DateTimeInterface $createdAt): void;

	public function getCreatedAt(): DateTimeInterface|null;

}
