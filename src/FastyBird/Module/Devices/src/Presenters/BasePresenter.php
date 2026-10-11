<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Presenters;

use FastyBird\Core\Presenters;
use Nette\Application;
use Override;

/**
 * Base module presenter
 */
abstract class BasePresenter extends Presenters\BasePresenter
{

	#[Override]
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
