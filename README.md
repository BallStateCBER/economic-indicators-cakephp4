# CBER Data Center: Economic Indicators

This is the repository for the [Economic Indicators](https://indicators.cberdata.org) website, developed by the
[Ball State University Center for Business and Economic Research](https://bsu.edu/cber).

## Understanding how data is handled
- `/src/Endpoints/EndpointGroups.php` defines **groups of endpoints** that are displayed together on the same page. Each
  endpoint corresponds to a "series" in the [FRED API](https://fred.stlouisfed.org/docs/api/fred/)
- The command `bin/cake update_stats` uses these definitions to query the FRED API and populate / update the `metrics`
  and `statistics` tables
- Each endpoint has one corresponding **metric** in the database with a matching `metrics.series_id` value
- Each **statistic** record is associated with one metric, but one of three possible data types, defined in
  `StatisticsTable`:
  - Value
  - Change since last year
  - Percent change since last year
- The `releases` table stores information pulled from the FRED API about dates in which primary sources are expected to
  release new data (which then becomes available to FRED, and then gets pulled into the Economic Indicators database)

## Keeping data updated
- `bin/cake update_stats` should be run at least daily, and can take several hours to complete
  - Run with the `--only-new` option to only check endpoints with releases on or before today that have not yet been
    imported, and only adds new stats, rather than also updating existing stats
  - Run with the `--ignore-lock` option to either allow multiple update processes to take place concurrently
    (not recommended) or to fix a process lock that failed to be cleared by the previous process
- `bin/cake update_release_dates` should be run daily
  - Run with the `--only-cache` option to only rebuild the cached release calendar
- `bin/cake make_spreadsheets` can be run manually if there's a problem with the pre-generated spreadsheets, but
  `bin/cake update_stats` also automatically (re)generates spreadsheets if needed
    - Run with the `--verbose` option to output information about memory usage
- `bin/cake update_cache` can be run manually to rebuild the cache of query results, though
  `bin/cake update_stats` updates the cache automatically, if appropriate
    - Run with the `--verbose` option to output information about memory usage
- Note that the FRED API occasionally fails to return a valid response, in which case these scripts will re-try the same
  request a limited number of times before giving up. Those requests will then be attempted again the next time an
  update script is invoked.
- The [FRED API request rate limit](https://fred.stlouisfed.org/docs/api/terms_of_use.html) is nonspecific, so there is
  currently no way to guarantee that it is not exceeded. However, there is a one-second delay between every request
  and a five-second delay after any error, so it is not anticipated that errors caused by exceeding the allowed rate
  will take place as long as only one instance of any update script is running at a time.
