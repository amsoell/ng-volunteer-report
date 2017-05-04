#!/usr/local/php5/bin/php
<?php

/**
 * Setup
 *
 * Pull in command line parameters
 * Display usage
 */
define('VOLWP_TOTAL', 'TOT');
define('VOLWP_NOTAVOLUNTEER', 1);
define('VOLWP_VOLUNTEER', 2);
define('VOLWP_NOTSTATED', 'Z');

$options = getopt('v', [
	'lga::',
	'datafile:',
	'outfile::',
	'precision::',
]);

// Default parameters
$options += [
	'precision' => 2
];

if (! (array_key_exists('datafile', $options))) {
	// Print usage instructions
	echo 'Usage: ' . basename(__FILE__) . ' --datafile=<datafile.csv> --lga=<LGA>';
	exit;
}

if (array_key_exists('lga', $options)) {
	// Split the LGA option into separate items
	$options['lga'] = explode(',', $options['lga']);
}

/**
 * Process the datafile
 */
if (array_key_exists('v', $options)) echo "Reading datafile...\n";
$datafile_handle = fopen($options['datafile'], 'r') or die("Couldn't open datafile");

if ($datafile_handle) {
	// Get the datafile keys
	$datafile_keys = fgetcsv($datafile_handle);
	$summary_data = [];

	// Get the data
	while ($chunk = fgetcsv($datafile_handle)) {
		// Exit if data doesn't match header columns
		if (count($chunk) != count($datafile_keys)) continue;

		// Key up the data for easier access
		$chunk = array_combine($datafile_keys, $chunk);

		if (!array_key_exists('lga', $options) || in_array($chunk['Region'], $options['lga'])) {
			// This is data we care about; Hang on to it.
			$chunk = array_combine($datafile_keys, $chunk);
			switch (strtoupper($chunk['VOLWP'])) {
				case VOLWP_TOTAL:
					update_total($summary_data, $chunk, 'total');

					break;
				case VOLWP_VOLUNTEER:
					update_total($summary_data, $chunk, 'volunteers');

					break;
				case VOLWP_NOTAVOLUNTEER:
					update_total($summary_data, $chunk, 'nonvolunteers');

					break;
				case VOLWP_NOTSTATED:
					update_total($summary_data, $chunk, 'unstated');
					break;
			}
		}
	}
} else {
	die("Datafile is unreadable");
}

/**
 * Wrapup code — output any data to the user
 */
if (array_key_exists('v', $options)) echo "Total applicable records read: " . count($datafile_data) . "\n";

foreach ($summary_data as $lga => $lga_summary) {
	echo "Average volunteer rate for " . $lga . ": ";
	if ($lga_summary['total']==0) {
		echo "No population data\n";
	} else {
		echo round($lga_summary['volunteers'] / $lga_summary['total'] * 100, $options['precision']) . "%\n";
	}
}

if (array_key_exists('outfile', $options)) {
	$outfile_handle = fopen($options['outfile'], 'w');

	// Headers first
	fputcsv($outfile_handle, $datafile_keys);
	foreach ($datafile_data as $record) {
		fputcsv($outfile_handle, $record);
	}

	if (array_key_exists('v', $options)) echo "Matching records saved to " . $options['outfile'] . "\n";
}


/**
 * SUPPORT FUNCTIONS
 */

/**
 * update_total
 *
 * @param  Array &$data   Array data to update
 * @param  Array $newdata New data to process
 * @param  String $key     Array key to add new data to
 */
function update_total(&$data, $newdata, $key) {
	if (isset($data[$newdata['Region']][$key])) {
		$total = $data[$newdata['Region']][$key];
	} else {
		$total = 0;
	}

	$data[$newdata['Region']][$key] = $total + $newdata['Value'];
}
