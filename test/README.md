Automated tests
==============

phpunit is saved in the vendor folder in the repo so installation with
`composer` (using `composer.json`) is not necessary (not best practice).
The `setup.sh` script is therefore not used.

Run the tests with: `./vendor/bin/phpunit CustomAPITest.php`.

This SQL statement shows user ids etc.: `SELECT * FROM clab.vtiger_portalinfo`


Test results
------------

150726

```
OK, but incomplete or skipped tests!
Tests: 11, Assertions: 23, Skipped: 2.
```


