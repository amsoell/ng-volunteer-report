volunteer-report
===

PHP-based command line utility to parse Australian census data

## Requirements

Requires PHP 5.6 or greater

## Usage

Get summary data by LGA

`volrpt.php --datafile <path>`

Get summary data for specific LGAs

`volrpt.php --datafile <path> --lga="Victoria,Goulburn,Moruya - Tuross Head"`

Get volunteer data by age group by LGA

`volrpt.php --datafile <path> --report=byage`

Get volunteer data by age group for specific LGAs

`volrpt.php --datafile <path> --report=byage --lga="Victoria,Goulburn,Moruya - Tuross Head"`

Specify output precision

`volrpt.php --datafile <path> --precision=5`

## Advanced reports

Advanced reports require the use of a MySQL database. To take advantage of this functionality, copy the `.volrpt-example` file to `.volrpt` and set your local database credentials. **Note**: Running this command will delete the `data` table within the database you specify.

Once the configuration file has been set, you can run the advanced report with:

`volrpt.php --datafile <path> --report=advanced`

## Roadmap

Program weaknesses that require improvement include:

1. Better error handling on database connectivity
2. Data integrity checks; The current version assumes clean, sanitized data
3. Tests! Specifically confirming calculations from the database match calculations straight from the CSV import
4. Sorting. Records are currently presented in about the order they appear in the initial CSV import.
5. Possibly a built-in export option. This is a lot of text to just dump to the console.
6. More user output. Would be useful to see a spinner or loading message when processing records so the user knows something is happening.
