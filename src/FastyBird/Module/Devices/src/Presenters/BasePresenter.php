<?php declare(strict_types = 1);

/**
 * BasePresenter.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Presenters
 * @since          1.0.0
 *
 * @date           21.06.24
 */

namespace FastyBird\Module\Devices\Presenters;

use FastyBird\Core\Presenters;
use Nette\Application;

/**
 * Base module presenter
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Presenters
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class BasePresenter extends Presenters\BasePresenter
{

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
