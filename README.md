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
- `bin/cake update_release_dates` should be run daily
- `bin/cake make_spreadsheets` can be run manually if there's a problem with the pre-generated spreadsheets, but
  `bin/cake update_stats` also automatically (re)generates spreadsheets if needed
- `bin/cake update_cache` can be run manually to rebuild the cache of query results, though
  `bin/cake update_stats` updates the cache automatically, if appropriate
