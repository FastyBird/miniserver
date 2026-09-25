<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Presenters;

use FastyBird\Core\Configuration;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Security\Access;
use FastyBird\Core\Security\Identity;
use Nette\Application;
use ReflectionClass;
use ReflectionMethod;

/**
 * Nette's presenters security trait
 *
 * @method Application\IPresenter getPresenter()
 * @method string storeRequest(string $expiration = '+ 10 minutes')
 */
trait HasAuthorization
{

	protected Configuration\Configuration $simpleAuthConfiguration;

	protected Access\AnnotationChecker $annotationChecker;

	protected Identity\User|null $simpleUser = null;

	public function injectSimpleAuth(
		Access\AnnotationChecker $annotationChecker,
		Configuration\Configuration $configuration,
		Identity\User|null $simpleUser = null,
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
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
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
