<?php declare(strict_types = 1);

/**
 * TSimpleAuth.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Application
 * @since          1.0.0
 *
 * @date           01.07.24
 */

namespace FastyBird\Core\Presenters\SimpleAuth;

use FastyBird\Core\Configuration;
use FastyBird\Core\Security\SimpleAuth as Security;
use FastyBird\Core\Security\SimpleAuth\Access;
use Nette\Application;
use ReflectionClass;
use ReflectionMethod;

/**
 * Nette's presenters security trait
 *
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @method Application\IPresenter getPresenter()
 * @method string storeRequest(string $expiration = '+ 10 minutes')
 */
trait TSimpleAuth
{

	protected Configuration\Configuration $simpleAuthConfiguration;

	protected Access\AnnotationChecker $annotationChecker;

	protected Security\User|null $simpleUser = null;

	public function injectSimpleAuth(
		Access\AnnotationChecker $annotationChecker,
		Configuration\Configuration $configuration,
		Security\User|null $simpleUser = null,
	): void
	{
		$this->annotationChecker = $annotationChecker;
		$this->simpleAuthConfiguration = $configuration;
		$this->simpleUser = $simpleUser;
	}

	/**
	 * @param mixed $element
	 *
	 * @throws Application\ForbiddenRequestException
	 * @throws Application\UI\InvalidLinkException
	 */
	public function checkRequirements(ReflectionClass|ReflectionMethod $element): void
	{
		$redirectUrl = $this->simpleAuthConfiguration->getRedirectUrl([
			'backlink' => $this->storeRequest(),
		]);

		$homeUrl = $this->simpleAuthConfiguration->getHomeUrl();

		try {
			parent::checkRequirements($element);

			if (!$this->annotationChecker->checkAccess(
				$element instanceof ReflectionClass ? $element->name : $element->class,
				$element instanceof ReflectionMethod ? $element->name : null,
			)) {
				throw new Application\ForbiddenRequestException();
			}
		} catch (Application\ForbiddenRequestException $ex) {
			if ($redirectUrl) {
				if ($this->simpleUser->isLoggedIn()) {
					$this->getPresenter()->redirectUrl($homeUrl);
				} else {
					$this->getPresenter()->redirectUrl($redirectUrl);
				}
			} else {
				throw $ex;
			}
		}
	}

}
