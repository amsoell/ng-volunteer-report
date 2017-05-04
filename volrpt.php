#!/usr/local/php5/bin/php
<?php

/**
 * Setup
 *
 * Pull in command line parameters
 * Display usage
 */
$options = getopt('v', [
	'lga:',
	'datafile:',
	'outfile::',
]);

if (! (array_key_exists('lga', $options) && array_key_exists('datafile', $options))) {
	// Print usage instructions
	echo 'Usage: ' . basename(__FILE__) . ' --datafile=<datafile.csv> --lga=<LGA>';
	exit;
}

/**
 * Process the datafile
 */
if (array_key_exists('v', $options)) echo "Reading datafile...\n";
$datafile_handle = fopen($options['datafile'], 'r') or die("Couldn't open datafile");

if ($datafile_handle) {
	// Get the datafile keys
	$datafile_keys = fgetcsv($datafile_handle);
	$datafile_data = [];

	// Get the data
	while ($chunk = fgetcsv($datafile_handle)) {
		// Exit if data doesn't match header columns
		if (count($chunk) != count($datafile_keys)) continue;

		// Key up the data for easier access
		$chunk = array_combine($datafile_keys, $chunk);

		if ($chunk['Region'] == $options['lga']) {
			// This is data we care about; Hang on to it.
			$datafile_data[] = array_combine($datafile_keys, $chunk);
		}
	}
} else {
	die("Datafile is unreadable");
}

/**
 * Wrapup code — output any data to the user
 */
if (array_key_exists('v', $options)) echo "Total applicable records read: " . count($datafile_data) . "\n";

if (array_key_exists('outfile', $options)) {
	$outfile_handle = fopen($options['outfile'], 'w');

	// Headers first
	fputcsv($outfile_handle, $datafile_keys);
	foreach ($datafile_data as $record) {
		fputcsv($outfile_handle, $record);
	}

	if (array_key_exists('v', $options)) echo "Matching records saved to " . $options['outfile'] . "\n";
}


