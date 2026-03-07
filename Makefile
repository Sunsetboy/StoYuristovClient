.PHONY: test docker-build docker-test

test:
	./vendor/bin/phpunit

docker-build:
	docker build -t sto-yuristov-client .

docker-test: docker-build
	docker run --rm sto-yuristov-client

