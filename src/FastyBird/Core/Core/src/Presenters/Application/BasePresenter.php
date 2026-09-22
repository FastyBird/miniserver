<?php declare(strict_types = 1);

namespace FastyBird\Core\Presenters\Application;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Presenters\SimpleAuth as SimpleAuthApplication;
use FastyBird\Core\UI\Application as UI;
use Nette\Application;
use function preg_match;

/**
 * Base application presenter
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

		// @phpstan-ignore return.type (Empty array is a valid layout list at runtime; narrowing to non-empty-array would be a behavioural change)
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
