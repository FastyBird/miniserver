<?php declare(strict_types = 1);

namespace FastyBird\Core\Helpers\WsServer\Formatter;

/**
 * WebSockets server output formater interface
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Logger
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IFormatter
{

	public function error(string $message): void;

	public function warning(string $message): void;

	public function note(string $message): void;

	public function caution(string $message): void;

}
