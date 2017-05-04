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

## Advanced reports

Advanced reports require the use of a MySQL database. To take advantage of this functionality, copy the `.volrpt-example` file to `.volrpt` and set your local database credentials. **Note**: Running this command will delete the `data` table within the database you specify.

Once the configuration file has been set, you can run the advanced report with:

`volrpt.php --datafile <path> --report=advanced`
