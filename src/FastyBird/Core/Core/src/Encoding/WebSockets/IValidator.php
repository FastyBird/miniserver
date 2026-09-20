<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\WebSockets;

/**
 * Encoding validation interface
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Encoding
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IValidator
{

	/**
	 * Verify a string matches the encoding type
	 *
	 * @param string $str      The string to check
	 * @param string $encoding The encoding type to check against
	 */
	public function checkEncoding(string $str, string $encoding): bool;

}
