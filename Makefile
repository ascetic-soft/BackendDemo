.PHONY: fix cs-check stan test check all

fix:
	vendor/bin/php-cs-fixer fix

cs-check:
	vendor/bin/php-cs-fixer fix --dry-run --diff

stan:
	vendor/bin/phpstan analyse

test:
	vendor/bin/phpunit

check: cs-check stan test

all: fix stan test
