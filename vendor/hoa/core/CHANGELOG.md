# 2.15.11.09

  * Fix CS. (Ivan Enderlin, 2015-10-29T22:03:18+01:00)

# 2.15.09.03

  * Remove the `bin` property in `composer.json`. (Ivan Enderlin, 2015-09-03T10:21:32+02:00)

# 2.15.09.01

  * Fix `resolve` use. (Metalaka, 2015-07-15T19:10:30+02:00)
  * Return `resolve` path without trailing DS. (Metalaka, 2014-12-09T21:48:08+01:00)
  * Fix CS. (Ivan Enderlin, 2015-08-05T08:51:19+02:00)
  * Constantize: Change ';' into constant `RS` as `RootSeparator`. (agerlier, 2014-05-27T13:39:16+02:00)
  * Add a `.gitignore` file. (Stéphane HULARD, 2015-08-03T11:24:10+02:00)
  * Check whether Throwable already exists. (Metalaka, 2015-07-21T15:23:00+02:00)
  * Update composer.json (jubianchi, 2015-07-13T16:20:31+02:00)
  * Remove binaries to prepare for hoa/cli (jubianchi, 2015-07-13T16:18:45+02:00)

# 2.15.07.07

  * Update retro-compatibility with PHP7. (Ivan Enderlin, 2015-07-07T14:11:11+02:00)

# 2.15.07.05

  * `stream_open` returns `false` if not a resource. (Ivan Enderlin, 2015-07-05T15:10:32+02:00)

# 2.15.06.02

  * Remove exception type-hint because of PHP7. (Ivan Enderlin, 2015-06-01T15:51:57+02:00)

# 2.15.06.01

  * Assert `$previous` still receive an `Exception`. (Ivan Enderlin, 2015-06-01T10:51:32+02:00)

# 2.15.05.29

  * Add new keywords from PHP7. (Ivan Enderlin, 2015-05-29T14:49:48+02:00)
  * Type-hint with `BaseException` when necessary. (Ivan Enderlin, 2015-05-29T11:34:14+02:00)
  * Implement fake Base-, Engine- and ParseException… (Ivan Enderlin, 2015-05-29T11:30:59+02:00)
  * Move to PSR-1 and PSR-2. (Ivan Enderlin, 2015-05-13T11:24:06+02:00)

# 2.15.04.13

  * Remove `setlocale`. (Ivan Enderlin, 2015-04-13T08:50:40+02:00)
  * `notify` no longer checks the source reference. (Ivan Enderlin, 2015-03-03T10:28:32+01:00)
  * Add the `stream_metadata` method on the wrapper. (Ivan Enderlin, 2015-02-26T17:16:03+01:00)

# 2.15.02.18

  * Allows third-parties to easily extend `hoa://`. (Ivan Enderlin, 2015-02-18T17:18:09+01:00)
  * Add the CHANGELOG.md file. (Ivan Enderlin, 2015-02-18T09:16:30+01:00)

# 2.15.01.24

  * Fix a bug. (Ivan Enderlin, 2015-01-23T18:52:18+01:00)

# 2.15.01.23

  * Use the new dispatcher. (Ivan Enderlin, 2015-01-23T17:28:41+01:00)
  * Remove `from`/`import` and update to PHP5.4. (Ivan Enderlin, 2015-01-22T09:52:00+01:00)
  * Move some commands in `Hoa\Devtools`. (Ivan Enderlin, 2015-01-21T16:57:59+01:00)
  * Format and simplify. (Ivan Enderlin, 2015-01-21T09:07:40+01:00)
  * Update `Hoa.php` to look for several `vendor/` locations. (mikeSimonson, 2015-01-14T11:46:55+01:00)
  * Load the autoloader when installed globally. (mikeSimonson, 2015-01-13T15:53:27+01:00)
  * Happy new year! (Ivan Enderlin, 2015-01-05T14:22:47+01:00)
  * Remove the unnecessary `ImportFilterIterator` class. (Ivan Enderlin, 2014-12-16T14:01:25+01:00)

# 2.14.12.10

  * Fix the load of Composer's autoloader. (Ivan Enderlin, 2014-12-10T10:00:11+01:00)
  * Fix a bug with Composer. (Ivan Enderlin, 2014-12-10T09:41:32+01:00)
  * Fix a bug in `hoa core:welcome`. (Ivan Enderlin, 2014-12-10T09:27:19+01:00)
  * Update `hoa://Library` with `WITH_COMPOSER` and PSR-4. (Ivan Enderlin, 2014-12-10T09:03:59+01:00)

# 2.14.11.26

  * Require `hoa/test`. (Alexis von Glasow, 2014-11-25T13:49:44+01:00)
  * Remove `ini_get` and `ini_date` for `date.timezone`. (Ivan Enderlin, 2014-11-18T16:30:23+01:00)

# 2.14.11.09

  * Cast the result to avoid printing it in `hoa`. (Ivan Enderlin, 2014-11-09T13:54:18+01:00)
  * Add ASCII art logo. (Ivan Enderlin, 2014-09-28T21:04:39+02:00)
  * Do not deal with un-namespaced classes in the autoloader. (Ivan Enderlin, 2014-09-26T11:53:05+02:00)
  * Definitively drop PHP5.3. (Ivan Enderlin, 2014-09-24T14:49:57+02:00)
  * Add `hoa/test` as a `require-dev`. (Ivan Enderlin, 2014-09-24T11:12:50+02:00)

# 2.14.09.23

  * Add `branch-alias`. (Stéphane PY, 2014-09-23T11:40:17+02:00)

# 2.14.09.17

  * Fix typos. (Ivan Enderlin, 2014-09-17T16:38:29+02:00)
  * Drop PHP5.3. (Ivan Enderlin, 2014-09-17T16:37:40+02:00)

(first snapshot)
