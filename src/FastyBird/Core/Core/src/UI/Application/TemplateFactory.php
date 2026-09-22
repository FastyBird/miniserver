<?php declare(strict_types = 1);

namespace FastyBird\Core\UI\Application;

use FastyBird\Core\Exceptions;
use function file_exists;
use function sprintf;

final class TemplateFactory
{

	/** @var array<string> */
	private array $layouts = [];

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function registerLayout(string $layout): void
	{
		if (!file_exists($layout)) {
			throw new Exceptions\InvalidArgument(sprintf('Provided layout file: "%s" does not exist', $layout));
		}

		$this->layouts[] = $layout;
	}

	/**
	 * @return array<string>
	 */
	public function getLayouts(): array
	{
		return $this->layouts;
	}

}
