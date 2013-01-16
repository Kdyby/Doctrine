#!/usr/bin/php
<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

$parseOptions = function () use ($_SERVER) {
	$options = array('quiet' => false, 'files' => array());
	foreach (array_keys(getopt('qh', array('quiet', 'help'))) as $arg) {
		switch ($arg) {
			case 'q':
			case 'quiet':
				$options['quiet'] = true;
				break;

			case 'h':
			case 'help':
			default:
				echo <<<HELP
usage: lint [-q] [path]

options:
	-q, --quiet:     disable verbose output
	-h, --help:      display this help screen

HELP;
				exit(0);
		}
	}

	stream_set_blocking(STDIN, FALSE);
	while ($line = trim(fgets(STDIN))) {
		$options['files'][] = $_SERVER['PWD'] . '/' . $line;
	}

	if (empty($options['files']) && $_SERVER['argc'] > 1) {
		foreach ($_SERVER['argv'] as $i => $arg) {
			if (substr($arg, 0, 1) === '-' || $i === 0) continue;
			$options['files'][] = $arg;
		}
	}

	if (empty($options['files'])) $options['files'][] = $_SERVER['PWD'];

	foreach ($options['files'] as $i => $file) {
		if (($options['files'][$i] = realpath($file)) !== false) continue;
		echo "$file is not a file or directory.\n";
		exit(1);
	}

	return $options;
};

$echo = function () use (&$context) {
	if ($context['quiet']) return;
	foreach (func_get_args() as $arg) echo $arg;
};

$lintFile = function ($path) use (&$echo, &$context) {
	if (substr($path, -4) != '.php') return;

	if ($context['filesCount'] % 63 == 0) {
		$echo("\n");
	}

	exec("php -l " . escapeshellarg($path) . " 2>&1 1> /dev/null", $output, $code);
	if ($code) {
		$context['errors'][] = implode($output);
		$echo('E');
	} else {
		$echo('.');
	}

	$context['filesCount']++;
};

$check = function ($path) use (&$check, &$lintFile, &$context) {
	if (!is_dir($path)) return $lintFile($path);
	foreach (scandir($path) as $item) {
		if ($item == '.' || $item == '..') continue;
		$check(rtrim($path, '/') . '/' . $item);
	}
};


$context = $parseOptions();
$context['filesCount'] = 0;
$context['errors'] = array();
foreach ($context['files'] as $file) $check($file);
if ($context['errors']) {
	$echo("\n\n", implode($context['errors']));
}

$echo(
	"\n\n", ($context['errors'] ? 'FAILED' : 'OK'),
	' (', $context['filesCount'], " files checked, ", count($context['errors']), " errors)\n"
);
exit($context['errors'] ? 1 : 0);
