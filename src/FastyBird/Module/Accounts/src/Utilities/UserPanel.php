<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Utilities;

use FastyBird\Module\Accounts\Security;
use Nette\Utils;
use Override;
use Tracy;
use const DIRECTORY_SEPARATOR;

/**
 * User panel for Debugger Bar
 */
final readonly class UserPanel implements Tracy\IBarPanel
{

	public function __construct(private Security\User $user)
	{
	}

	/**
	 * Renders tab
	 */
	#[Override]
	public function getTab(): string
	{
		return Utils\Helpers::capture(function (): void {
			// phpcs:ignore SlevomatCodingStandard.Variables.UnusedVariable.UnusedVariable
			$status = $this->user->isLoggedIn();

			require __DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'UserPanel.tab.phtml';
		});
	}

	/**
	 * Renders panel
	 */
	#[Override]
	public function getPanel(): string
	{
		return Utils\Helpers::capture(function (): void {
			// phpcs:ignore SlevomatCodingStandard.Variables.UnusedVariable.UnusedVariable
			$user = $this->user;

			require __DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'UserPanel.panel.phtml';
		});
	}

}
