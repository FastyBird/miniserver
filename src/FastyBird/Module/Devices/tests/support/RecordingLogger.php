<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Support;

use Override;
use Psr\Log;
use Stringable;

final class RecordingLogger extends Log\AbstractLogger
{

	/** @var list<array{level: string, message: string, context: array<mixed>}> */
	public array $records = [];

	/**
	 * @param array<mixed> $context
	 */
	#[Override]
	public function log(mixed $level, string|Stringable $message, array $context = []): void
	{
		$this->records[] = [
			'level' => (string) $level,
			'message' => (string) $message,
			'context' => $context,
		];
	}

	/**
	 * @return list<array{level: string, message: string, context: array<mixed>}>
	 */
	public function at(string $level): array
	{
		$records = [];

		foreach ($this->records as $record) {
			if ($record['level'] === $level) {
				$records[] = $record;
			}
		}

		return $records;
	}

}
