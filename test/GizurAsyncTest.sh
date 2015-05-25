#!/bin/bash
echo "Running parallel unit tests..."
for i in {1..20}
do
   echo "Test run $i"
   phpunit --filter GetPicklist GizurRESTAPITest.php >> output &
done
tail -f output
