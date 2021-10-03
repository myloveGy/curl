.PHONY: start
start:
	/usr/bin/php -S  0.0.0.0:8080 -t tests/mocks/ &

.PHONY: test
test:
	vendor/bin/phpunit --configuration phpunit.xml
