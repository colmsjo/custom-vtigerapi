Automated tests
==============

phpunit is saved in the vendor folder in the repo so installation with
`composer` (using `composer.json`) is not necessary (not best practice).
The `setup.sh` script is therefore not used.

Run the tests with: `./vendor/bin/phpunit CustomAPITest.php`.

This SQL statement shows user ids etc.: `SELECT * FROM clab.vtiger_portalinfo`


Test results
------------

150710, The tests have 4 failures (should of course have been fixed...):

```
There were 4 failures:

1) Custom_API_Test::testGetTroubleTicketInoperationListWithFilter
Failed asserting that null is an instance of class "stdClass".

/apps/custom-vtigerapi/test/CustomAPITest.php:423

2) Custom_API_Test::testGetTroubleTicketDamagedList
Failed asserting that null is an instance of class "stdClass".

/apps/custom-vtigerapi/test/CustomAPITest.php:466

3) Custom_API_Test::testGetTroubleTicketFromId
Checking validity of response
Failed asserting that true matches expected false.

/apps/custom-vtigerapi/test/CustomAPITest.php:508

4) Custom_API_Test::testCreateTroubleTicketWithOutDocument
Failed asserting that null is an instance of class "stdClass".

/apps/custom-vtigerapi/test/CustomAPITest.php:686

FAILURES!
Tests: 11, Assertions: 23, Failures: 4, Skipped: 2.
```


