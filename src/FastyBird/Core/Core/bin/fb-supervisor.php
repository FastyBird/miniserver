<?php declare(strict_types = 1);

$stdIn = STDIN;
$stdOut = STDOUT;

fwrite($stdOut, "READY\n");

// @phpstan-ignore-next-line
while (true) {
	$line = fgets($stdIn);

	if ($line === false) {
		continue;
	}

	$line = trim($line);

	$match = null;

	if (preg_match('/eventname:(.*?) /', $line, $match) !== false) {
		if (in_array($match[1], ['PROCESS_STATE_EXITED', 'PROCESS_STATE_STOPPED', 'PROCESS_STATE_FATAL'], true)) {
			exec('kill -15 ' . file_get_contents('/run/supervisord.pid'));
		}
	}

	fwrite($stdOut, "RESULT 2\nOK");

	sleep(1);

	fwrite($stdOut, "READY\n");
}
