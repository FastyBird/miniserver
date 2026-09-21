<?php declare(strict_types = 1);

/**
 * TokenReader.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Security
 * @since          0.1.0
 *
 * @date           09.07.20
 */

namespace FastyBird\Core\Security\SimpleAuth;

use FastyBird\Core\Constants\SimpleAuth;
use FastyBird\Core\Exceptions;
use Lcobucci\JWT;
use Nette;
use Psr\Http\Message\ServerRequestInterface;
use function is_array;
use function is_string;
use function preg_match;
use function reset;

/**
 * JW token reader
 *
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Security
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class TokenReader
{

	use Nette\SmartObject;

	public function __construct(private readonly TokenValidator $tokenValidator)
	{
	}

	/**
	 * @throws Exceptions\UnauthorizedAccess
	 */
	public function read(ServerRequestInterface $request): JWT\UnencryptedToken|null
	{
		$headerJWT = $request->hasHeader(SimpleAuth\Constants::TOKEN_HEADER_NAME)
			? $request->getHeader(SimpleAuth\Constants::TOKEN_HEADER_NAME)
			: null;

		$headerJWT = is_array($headerJWT) ? reset($headerJWT) : $headerJWT;

		if (
			is_string($headerJWT)
			&& preg_match(SimpleAuth\Constants::TOKEN_HEADER_REGEXP, $headerJWT, $matches) === 1
			&& $matches[1] !== ''
		) {
			$token = $this->tokenValidator->validate($matches[1]);

			if ($token === null) {
				throw new Exceptions\UnauthorizedAccess('Access token is not valid');
			}

			return $token;
		}

		return null;
	}

}
