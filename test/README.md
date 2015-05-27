Automated tests
==============

Start thr vtiger and beservices.

Everything is setup by the script `setup.sh` that is executed when the container is built.

Start apache: `/start.sh`

Run the tests: `./vendor/bin/phpunit CustomAPITest.php`. 

This SQL statement shows user ids etc.: `SELECT * FROM clab.vtiger_portalinfo`
