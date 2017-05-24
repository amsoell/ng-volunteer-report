volunteer-report
===

**ng-volunteer-report** is the result of a code challenge presented in 2017. The result is a PHP-based command line utility used to parse Australian census data

**Project Specifications**

> Using the data in the file ["B19 Voluntary Work for an Organisation or Group by Age by Sex"](http://stat.data.abs.gov.au/Index.aspx?DataSetCode=ABS_CENSUS2011_B19) from the 2011 Australian Census:
> 
> Do the following tasks WITHOUT a database:
> 
> 1.  Calculate the average percentage of volunteers compared with LGA's population size
>     * For one LGA; or
>     * For up to 10 LGA's (specified by the "State" and "Region" CSV
>       fields); or
>     * For all LGA's
> 2.  Generate a 2D matrix of "Age" to "Percent of volunteers"
>     * For one LGA; or
>     * For up to 20 LGA's (specified by the "State" and "Region" CSV
>       fields)
> 
> Do the following task WITH a database:
> 
> 3.  For each state / territory in Australia except South Australia, calculate the total actual number (i.e. not percentage) of non-volunteers, for all LGA's that have less than or equal to 3 vowels in their name (exactly as it appears in the "Region" CSV field), excluding the 3 LGA's for each state / territory that have the lowest number of non-volunteers
> 
> Write a command-line script that exposes the above three pieces of functionality, with options / arguments as required, and that prints out the results as lines of text (or as a simple textual table) to the terminal.

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
