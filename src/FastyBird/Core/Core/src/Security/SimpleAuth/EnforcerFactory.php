<?php declare(strict_types = 1);

/**
 * EnforcerFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Security
 * @since          0.1.0
 *
 * @date           21.07.24
 */

namespace FastyBird\Core\Security\SimpleAuth;

use Casbin;
use FastyBird\Core\Exceptions;

/**
 * Class security annotation checker
 *
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Security
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class EnforcerFactory
{

	private Casbin\CachedEnforcer|null $enforcer = null;

	public function __construct(
		private readonly string $modelFile,
		private readonly Casbin\Persist\Adapter $adapter,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getEnforcer(): Casbin\CachedEnforcer
	{
		if ($this->enforcer === null) {
			try {
				$this->enforcer = new Casbin\CachedEnforcer($this->modelFile, $this->adapter);
			} catch (Casbin\Exceptions\CasbinException $ex) {
				throw new Exceptions\InvalidState('Failed to create an enforcer', $ex->getCode(), $ex);
			}
		}

		return $this->enforcer;
	}

}
