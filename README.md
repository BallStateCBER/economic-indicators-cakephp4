# CBER Data Center: Economic Indicators

This is the repository for the [Economic Indicators](http://indicators.cberdata.org) website, developed by the
[Ball State University Center for Business and Economic Research](http://bsu.edu/cber).

## Understanding how data is handled
- `/src/Endpoints/FredEndpoints.php` defines **endpoints** corresponding to "series" in the
  [FRED API](https://fred.stlouisfed.org/docs/api/fred/)
- `/src/Endpoints/EndpointGroups.php` defines **groups of endpoints** that are displayed together on the same page
- The command `bin/cake data_updater` uses these definitions to query the FRED API and populate / update the `metrics`
  and `statistics` tables
- Each endpoint has one corresponding **metric** in the database with a matching `metrics.series_id` value
- Each **statistic** record is associated with one metric, but one of three possible data types, defined in
  `StatisticsTable`:
  - Value
  - Change since last year
  - Percent change since last year
- The command `bin/cake update_cache` can be used to rebuild the cache of query results, though the command
  `bin/cake data_updater` updates the cache automatically, if appropriate
- The `releases` table stores information pulled from the FRED API about dates in which primary sources are expected to
  release new data (which then becomes available to FRED, and then gets pulled into the Economic Indicators database)
- The `bin/cake update_release_dates` command can be run to update the `releases` table
