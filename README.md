volunteer-report
===

PHP-based command line utility to parse Australian census data

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
