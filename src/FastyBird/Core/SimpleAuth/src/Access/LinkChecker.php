<?php declare(strict_types = 1);

/**
 * LinkChecker.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Security
 * @since          0.1.0
 *
 * @date           24.07.24
 */

namespace FastyBird\Core\SimpleAuth\Access;

use FastyBird\Core\SimpleAuth\Exceptions;
use Nette;
use Nette\Application;
use Nette\Application\UI;
use ReflectionException;
use function array_merge;
use function array_pop;
use function array_shift;
use function assert;
use function class_exists;
use function explode;
use function implode;
use function is_string;
use function str_contains;
use function strlen;
use function ucfirst;

/**
 * Create link access checker
 *
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Access
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class LinkChecker implements Checker
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	public function __construct(
		private readonly Application\IPresenterFactory $presenterFactory,
		private readonly Application\Application $application,
		private readonly CheckRequirements $requirementsChecker,
	)
	{
	}

	/**
	 * Check whenever current user is allowed to use given link
	 *
	 * @param mixed $element etc "this", ":Admin:Show:default"
	 *
	 * @throws Application\InvalidPresenterException
	 * @throws Exceptions\InvalidState
	 * @throws ReflectionException
	 */
	public function isAllowed(mixed $element): bool
	{
		assert(is_string($element));

		[$presenter, $action] = $this->formatLink($element);

		if ($presenter === null) {
			throw new Exceptions\InvalidState('Presenter name could not be determined.');
		}

		$presenterClass = $this->presenterFactory->getPresenterClass($presenter);

		if (!class_exists($presenterClass)) {
			throw new Exceptions\InvalidState('Presenter class was not found.');
		}

		$presenterReflection = new UI\ComponentReflection($presenterClass);

		if (!$this->requirementsChecker->isAllowed($presenterReflection)) {
			return false;
		}

		if ($action === null) {
			return true;
		}

		$actionKey = UI\Presenter::ActionKey . ucfirst($action);

		return !$presenterReflection->hasMethod($actionKey)
			|| $this->requirementsChecker->isAllowed($presenterReflection->getMethod($actionKey));
	}

	/**
	 * Format link to format array('module:submodule:presenter', 'action')
	 *
	 * @return array<string|null>
	 */
	public function formatLink(string $destination): array
	{
		$presenter = $this->application->getPresenter();
		$presenter = $presenter instanceof UI\Presenter ? $presenter : null;

		if ($destination === 'this') {
			return [$presenter?->getName(), $presenter?->getAction()];
		}

		$parts = explode(':', $destination);

		if ($destination[0] != ':') {
			$current = explode(':', $presenter?->getName() ?? '');

			if (str_contains($destination, ':')) {
				// Remove presenter
				array_pop($current);
			}

			$parts = array_merge($current, $parts);

		} else {
			// Remove empty
			array_shift($parts);
		}

		if ($destination[strlen($destination) - 1] == ':') {
			// Remove empty
			array_pop($parts);

			$action = UI\Presenter::DefaultAction;

		} else {
			$action = array_pop($parts);
		}

		return [implode(':', $parts), $action];
	}

}
