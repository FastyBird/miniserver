<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Encoding\Objects;

/**
 * Source object interface
 */
interface ISourceObject
{

	public function getPointer(): string|null;

	public function getParameter(): string|null;

}
