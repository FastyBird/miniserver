<?php declare(strict_types = 1);

/**
 * BasePresenter.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     Presenters
 * @since          1.0.0
 *
 * @date           16.06.24
 */

namespace FastyBird\Core\Application\Presenters;

use FastyBird\Core\Application\Exceptions;
use FastyBird\Core\Application\UI;
use FastyBird\SimpleAuth\Application as SimpleAuthApplication;
use Nette\Application;
use function preg_match;

/**
 * Base application presenter
 *
 * @package        FastyBird:Application!
 * @subpackage     Presenters
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class BasePresenter extends Application\UI\Presenter
{

	use SimpleAuthApplication\TSimpleAuth;

	private UI\TemplateFactory|null $templateFactory = null;

	public function injectTemplateFactory(UI\TemplateFactory $templateFactory): void
	{
		$this->templateFactory = $templateFactory;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function formatLayoutTemplateFiles(): array
	{
		if (
			$this->layout !== null
			&& $this->layout !== ''
			&& preg_match('#/|\\\\#', (string) $this->layout) === 1
		) {
			return [(string) $this->layout];
		}

		if ($this->templateFactory?->getLayouts() === []) {
			throw new Exceptions\InvalidState('No layouts are specified.');
		}

		// @phpstan-ignore-next-line return.type (Empty array is a valid layout list at runtime; narrowing to non-empty-array would be a behavioural change)
		return $this->templateFactory?->getLayouts() ?? [];
	}

	public function formatTemplateFiles(): array
	{
		[, $presenter] = Application\Helpers::splitName($this->getName() ?? '');

		$dir = __DIR__ . '/../../templates/';

		return [
			"$dir/presenters/$presenter/$this->view.latte",
			"$dir/presenters/$presenter.$this->view.latte",
			"$dir/presenters/$presenter.latte",
		];
	}

}
