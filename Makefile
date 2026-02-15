.PHONY: fix cs-check stan deptrac test check all

fix:
	vendor/bin/php-cs-fixer fix

cs-check:
	vendor/bin/php-cs-fixer fix --dry-run --diff

stan:
	vendor/bin/phpstan analyse

deptrac:
	vendor/bin/deptrac analyse

test:
	vendor/bin/phpunit

check: cs-check stan deptrac test

all: fix stan deptrac test
