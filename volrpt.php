#!/usr/bin/php
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

// Get command line parameters
$options = getopt('v', [
	'lga::',
	'datafile:',
	'report::',
	'precision::',
]);

// Fallback defaults
$options += [
	'precision' => 2,
	'report'    => 'overall'
];

if (! (array_key_exists('datafile', $options))) {
	// Print usage instructions
	echo 'Usage: ' . basename(__FILE__) . ' --datafile=<path> [--lga=<LGA>] [--precision=2] [--report=<report>]';
	exit;
}

if (array_key_exists('lga', $options)) {
	// Split the LGA option into separate items
	$options['lga'] = explode(',', $options['lga']);
}

// Look for configuration file for database connection details
if (file_exists('.volrpt')) {
	$db_config = parse_ini_file('.volrpt', true);
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

	// If database parameters are set, add the data for advanced reporting
	if (($options['report'] == 'advanced') && isset($db_config['database'])) {
		if (array_key_exists('v', $options)) echo "Establishing database connection\n";
		$db = new mysqli(	$db_config['database']['hostname'],
							$db_config['database']['username'],
							$db_config['database']['password'],
							$db_config['database']['database']);
		if ($db->connect_error) die('Could not make database connection: ' .  mysqli_connect_error());

		// Delete the existing table and create it again
		$db->query('DROP TABLE IF EXISTS data');
		$db->query('
			CREATE TABLE data (
				state varchar(50) NOT NULL,
				lga   varchar(50) NOT NULL,
				total int NOT NULL DEFAULT 0,
				nonvolunteers int NOT NULL DEFAULT 0,
				PRIMARY KEY (state, lga)
			)');
	}

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
					if (isset($db)) add_to_database($db, $chunk, 'total');

					break;
				case VOLWP_VOLUNTEER:
					update_total($summary_data, $chunk, 'volunteers');

					break;
				case VOLWP_NOTAVOLUNTEER:
					update_total($summary_data, $chunk, 'nonvolunteers');
					if (isset($db)) add_to_database($db, $chunk, 'nonvolunteers');

					break;
				case VOLWP_NOTSTATED:
					update_total($summary_data, $chunk, 'unstated');

					break;
			}
		}
	}

	fclose($datafile_handle);
} else {
	die("Datafile is unreadable");
}

// Do additional calculations for per-age-group statistics
$age_data = [];
foreach ($summary_data as $lga_key => $lga_data) {
	foreach ($lga_data['ages'] as $age_key => $data) {
		if ($data['total'] == 0) {
			$val = 0;
		} else {
			$val = $data['volunteers'] / $data['total'] * 100;
		}

		$age_data[$lga_key][$age_key] = $val;
	}
}

/**
 * Wrapup code — output any data to the user
 */
if (array_key_exists('v', $options)) echo "Total applicable records read: " . count($summary_data) . "\n";

switch (strtolower($options['report'])) {
	case 'advanced':
		if (!isset($db)) die("Invalid database connection; Check your .volrpt configuration file");

		// Loop one: Get all states and a count of LGAs in each state that have less than 4 vowels
		$rs_states = $db->query("SELECT state, COUNT(lga) AS lga_count FROM data WHERE lga NOT REGEXP '.*[aeiou].*[aeiou].*[aeiou].*[aeiou].*' AND state!=lga AND state!='South Australia' group by state");
		while ($rec_state = $rs_states->fetch_object()) {
			// Variable these up so we don't get a "by reference" error in bind_param
			$state = (string)$rec_state->state;
			$count = max((int)$rec_state->lga_count - 3, 0); // If there are less than 3 LGAs I guess we get zero?

			echo "State: $rec_state->state\n";

			// Loop two: For each state, get all LGAs that have less than 4 vowels.
			// Order by nonvolunteer count and imit to the count from the previous query minus 3 to eliminate the bottom 3 results
			$rs_lga = $db->query("SELECT lga, nonvolunteers FROM data WHERE state='" . addslashes($state) . "' AND lga NOT REGEXP '.*[aeiou].*[aeiou].*[aeiou].*[aeiou].*' ORDER BY nonvolunteers DESC LIMIT " . addslashes($count));
			while ($rec_lga = $rs_lga->fetch_object()) {
				echo "\t" . $rec_lga->lga . " nonvolunteer count: " . $rec_lga->nonvolunteers . "\n";
			}
		}

		break;
	case 'byage':
		display_matrix($age_data);
		break;
	default:
		foreach ($summary_data as $lga => $lga_summary) {
			echo "Overall volunteer rate for " . $lga . ": ";
			if ($lga_summary['total']==0) {
				echo "No population data\n";
			} else {
				echo round($lga_summary['volunteers'] / $lga_summary['total'] * 100, $options['precision']) . "%\n";
			}
		}
		break;
}

if (isset($db)) $db->close();

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
	// Update total
	if (isset($data[$newdata['Region']][$key])) {
		$total = $data[$newdata['Region']][$key];
	} else {
		$total = 0;
	}

	$data[$newdata['Region']][$key] = $total + $newdata['Value'];

	// Update age data
	if (isset($data[$newdata['Region']]['ages'][$newdata['AGE']][$key])) {
		$total = $data[$newdata['Region']]['ages'][$newdata['AGE']][$key];
	} else {
		$total = 0;
	}

	$data[$newdata['Region']]['ages'][$newdata['AGE']][$key] = $total + $newdata['Value'];
}

function add_to_database($db, $data, $key) {
	// Insert record or update existing record
	$sql = $db->prepare("INSERT INTO data (state, lga, ".$key.") VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE ".$key."=".$key."+?");
	$sql->bind_param('ssii', $data['State'], $data['Region'], $data['Value'], $data['Value']);
	$sql->execute();
}

function display_matrix($data) {
	global $options;

	// Get the array keys so we can calculat column widths
	$d1_keys = array_unique(array_keys($data));
	$d1_width = max(array_map('strlen', $d1_keys)) + 2;

	$d2_keys = array_unique(call_user_func_array('array_merge', array_map('array_keys', array_values($data))));
	$d2_width = max(array_map('strlen', $d2_keys)) + 2;

	// Formatting strings
	//
	// OK, this is complicated. But essentially we're just laying out the spacing of the grid.
	// It's just a lot of math based on the length of the array keys and the precision of the
	// calculations
	$format_data = "|%" . (7 + $options['precision']) . "." . $options['precision'] . "f%% ";
	$format_header = "|%" . max([$d2_width, 8 + $options['precision']]) . "s ";
	$row_separator = str_repeat('-', $d1_width + ((max([$d2_width, 8 + $options['precision']]) + 2) * count($d2_keys)));

	// Output headers
	echo sprintf("%-" . $d1_width . "s", '');
	foreach ($d2_keys as $key) {
		echo sprintf($format_header, $key);
	}
	echo "\n" . $row_separator . "\n";

	foreach ($data as $lga_key => $lga_data) {
		echo sprintf("%-" . $d1_width . "s", $lga_key);
		foreach ($d2_keys as $d2) {
			if (array_key_exists($d2, $lga_data)) {
				$val = $lga_data[$d2];
			} else {
				$val = '';
			}
			echo sprintf($format_data, $val);
		}
		echo "\n";
	}

}
