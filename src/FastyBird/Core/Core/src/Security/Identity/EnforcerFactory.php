<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Identity;

use Casbin;
use FastyBird\Core\Exceptions;

/**
 * Class security annotation checker
 */
final class EnforcerFactory
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
