<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Helpers\Formatter;

/**
 * WebSockets server output formater interface
 */
interface IFormatter
{

	public function error(string $message): void;

	public function warning(string $message): void;

	public function note(string $message): void;

	public function caution(string $message): void;

}
