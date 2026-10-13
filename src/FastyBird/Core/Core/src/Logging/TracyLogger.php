<?php declare(strict_types = 1);

namespace FastyBird\Core\Logging;

use Override;
use Throwable;
use Tracy;

/**
 * Tracy's own logger, for an error Tracy logs before the container hands logging to Monolog: it
 * writes the blue screen through SensitiveData, which hides the request's credentials and writes
 * no markdown report
 */
final class TracyLogger extends Tracy\Logger
{

	#[Override]
	protected function logException(Throwable $exception, string|null $file = null): string
	{
		$file ??= $this->getExceptionFile($exception);

		SensitiveData::renderBlueScreen(Tracy\Debugger::getBlueScreen(), $exception, $file);

		return $file;
	}

}
