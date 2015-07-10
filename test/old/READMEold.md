Automated tests
===============

Notes
----

Print PHP include path: `php -r 'foreach (explode(":", get_include_path()) as $path) echo $path . PHP_EOL;'`

Find php.ini that is used: `php -r 'phpinfo();'|grep php.ini`



Old Makefile
------------

	all:

	test:
		phpunit tests

	doc:
		phpdoc -t docs/api -d api/
		docco -o docs/gizur-admin             applications/gizur-admin/js/*.js
		docco -o docs/gizur-admin/collections applications/gizur-admin/js/collections/*.js
		docco -o docs/gizur-admin/libs        applications/gizur-admin/js/libs/*.js
		docco -o docs/gizur-admin/models      applications/gizur-admin/js/models/*.js
		docco -o docs/gizur-admin/views       applications/gizur-admin/js/views/*.js

	clean:
		rm -rf docs


	install2:
		curl -sS https://getcomposer.org/installer | php
		mv composer.phar composer
		./composer install
		npm install doccoh

	clean2:
		rm -rf docs build cache

	test2:
		./vendor/bin/phpunit tests 
		cd api/protected/tests && ../../../vendor/bin/phpunit .

	lint2:
		./vendor/bin/phpcs --standard=Zend api/protected/components/*.php \
						   api/protected/config/*.php \
						   api/protected/controllers/*.php \
						   api/protected/models/*.php \
						   api/protected/tests/*.php \
						   tests/*.php
		./vendor/bin/phpcs --standard=Zend applications/clab/trailer-app-portal/protected/components/*.php \
						   applications/clab/trailer-app-portal/protected/config/*.php \
						   applications/clab/trailer-app-portal/protected/controllers/*.php \
						   applications/clab/trailer-app-portal/protected/models/*.php \
						   applications/clab/trailer-app-portal/protected/tests/*.php

	docs2:
		php ./vendor/bin/sami.php update --force sami-config.php
		./node_modules/doccoh/bin/doccoh -o docs/gizur-admin             applications/gizur-admin/js/*.js
		./node_modules/doccoh/bin/doccoh -o docs/gizur-admin/collections applications/gizur-admin/js/collections/*.js
		./node_modules/doccoh/bin/doccoh -o docs/gizur-admin/libs        applications/gizur-admin/js/libs/*.js
		./node_modules/doccoh/bin/doccoh -o docs/gizur-admin/models      applications/gizur-admin/js/models/*.js
		./node_modules/doccoh/bin/doccoh -o docs/gizur-admin/views       applications/gizur-admin/js/views/*.js
		./node_modules/doccoh/bin/doccoh -o docs/applications/gizursaas  applications/gizursaas/config/*.js

	coverage2:
		./vendor/bin/phpunit --coverage-html ./coverage-report.html tests 
		cd api/protected/tests && ../../../vendor/bin/phpunit --coverage-html ../../../coverage-report2.html .

	lint3:
		./vendor/bin/phpcs --standard=Zend applications/cikab/php_batches/php-interfaces/sales_orders/*.php \
						   applications/cikab/php_batches/php-interfaces/*.php \
	                                           applications/cikab/php_batches/php-interfaces/reports/*.sh \
	                                           applications/cikab/php_batches/php-interfaces/sales_orders/*.sh \
