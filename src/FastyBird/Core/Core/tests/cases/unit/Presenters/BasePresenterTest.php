<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Presenters;

use FastyBird\Core\Presenters;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use function dirname;
use function realpath;

final class BasePresenterTest extends TestCase
{

	/**
	 * BasePresenter::formatTemplateFiles() locates the `templates/` directory relative to its
	 * own file via `__DIR__`. That directory arithmetic is not exercised anywhere else --
	 * `GET /` 404s by design (no route is registered), so nothing ever dispatches a presenter
	 * -- and is therefore silent if wrong: a class-level move of BasePresenter.php that does
	 * not also correct its relative depth would ship a `$dir` pointing at a directory that
	 * does not exist, undetected by every other gate. This test resolves the path
	 * `formatTemplateFiles()` computes independently, via reflection on the class's own file
	 * rather than by repeating BasePresenter's arithmetic, and asserts it lands on the real
	 * `templates/presenters` directory.
	 */
	public function testFormatTemplateFilesResolvesTheRealTemplatesDirectory(): void
	{
		$presenter = new Presenters\DefaultPresenter();

		$templateFiles = $presenter->formatTemplateFiles();

		// dirname() of "$dir/presenters/$presenter.latte" is "$dir/presenters", regardless of
		// the (here unset) presenter name -- the directory prefix is what this test verifies.
		$resolvedPresentersDir = realpath(dirname($templateFiles[2]));

		self::assertNotFalse(
			$resolvedPresentersDir,
			'formatTemplateFiles() computed a "presenters" directory that does not exist.',
		);

		$packageRoot = dirname((string) (new ReflectionClass(Presenters\DefaultPresenter::class))->getFileName(), 3);
		$expectedPresentersDir = realpath($packageRoot . '/templates/presenters');

		self::assertNotFalse($expectedPresentersDir, 'Core\'s own templates/presenters directory does not exist.');
		self::assertSame($expectedPresentersDir, $resolvedPresentersDir);
	}

}
