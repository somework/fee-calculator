#!/usr/bin/env php
<?php
declare(strict_types=1);

if ($argc < 3) {
    fwrite(STDERR, "Usage: php bin/check-coverage.php <clover-file> <min-coverage>\n");
    exit(1);
}

[$script, $coverageFile, $thresholdArg] = $argv;

if (!is_file($coverageFile)) {
    fwrite(STDERR, sprintf('Coverage file "%s" not found.%s', $coverageFile, PHP_EOL));
    exit(1);
}

$threshold = (float) $thresholdArg;
if ($threshold < 0 || $threshold > 100) {
    fwrite(STDERR, 'Threshold must be between 0 and 100.' . PHP_EOL);
    exit(1);
}

$contents = file_get_contents($coverageFile);
if ($contents === false) {
    fwrite(STDERR, sprintf('Unable to read coverage file "%s".%s', $coverageFile, PHP_EOL));
    exit(1);
}

libxml_use_internal_errors(true);
$xml = simplexml_load_string($contents);
if ($xml === false) {
    fwrite(STDERR, 'Unable to parse Clover coverage report.' . PHP_EOL);
    foreach (libxml_get_errors() as $error) {
        fwrite(STDERR, trim($error->message) . PHP_EOL);
    }
    exit(1);
}

$metrics = $xml->xpath('//metrics');
if ($metrics === false || $metrics === []) {
    fwrite(STDERR, 'No metrics found in Clover coverage report.' . PHP_EOL);
    exit(1);
}

$globalMetrics = $metrics[0];
$statements = (float) $globalMetrics['statements'];
$coveredStatements = (float) $globalMetrics['coveredstatements'];

$coverage = $statements > 0.0 ? ($coveredStatements / $statements) * 100.0 : 100.0;

$formattedCoverage = number_format($coverage, 2);
$formattedThreshold = number_format($threshold, 2);

if ($coverage + 1e-6 < $threshold) {
    fwrite(
        STDERR,
        sprintf(
            'Coverage regression detected: %.2f%% is below the %.2f%% threshold.%s',
            $coverage,
            $threshold,
            PHP_EOL
        )
    );
    exit(1);
}

echo sprintf('Coverage OK: %s%% >= %s%%%s', $formattedCoverage, $formattedThreshold, PHP_EOL);
