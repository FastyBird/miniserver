<?php declare(strict_types = 1);

/**
 * IErrorObject.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 * @since          0.2.0
 *
 * @date           19.05.21
 */

namespace FastyBird\Core\Encoding\JsonApi\Objects;

/**
 * Error object interface
 *
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IErrorObject
{

	public function getId(): string|null;

	public function hasLinks(): bool;

	public function getLinks(): ILinkObjectCollection;

	public function getStatus(): int|null;

	public function getCode(): string|null;

	public function getTitle(): string|null;

	public function getDetail(): string|null;

	public function getSource(): ISourceObject|null;

	public function hasMeta(): bool;

	public function getMeta(): IMetaObjectCollection;

}
