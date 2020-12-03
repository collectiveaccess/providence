
BUILD=./build
DIST=./dist

test:
	./vendor/bin/phpunit

init:
	composer install
	mkdir -p $(BUILD)/api
	mkdir -p $(BUILD)/code-browser
	mkdir -p $(BUILD)/coverage
	mkdir -p $(BUILD)/logs
	mkdir -p $(BUILD)/pdepend
	mkdir -p $(BUILD)/phpmd
	mkdir -p $(DIST)

update:
	composer update

dist:
	composer archive --format=zip --dir=$(DIST)

analyze: dependencies mess cpd sniff docs browse

dependencies:
	./vendor/bin/pdepend --jdepend-xml=$(BUILD)/logs/jdepend.xml \
		--jdepend-chart=$(BUILD)/pdepend/dependencies.svg \
		--overview-pyramid=$(BUILD)/pdepend/overview-pyramid.svg \
		lib

mess:
	./vendor/bin/phpmd lib html codesize,design,naming,unusedcode --reportfile $(BUILD)/phpmd/index.html

cpd:
	./vendor/bin/phpcpd --log-pmd $(BUILD)/logs/pmd-cpd.xml lib

sniff:
	./vendor/bin/phpcs --report=checkstyle \
		--extensions=php \
		--ignore=*/test/* \
		--report-file=$(BUILD)/logs/checkstyle.xml \
		--standard=PEAR \
		lib

docs:
	./vendor/bin/phpdoc -d lib -t $(BUILD)/api

browse:
	./vendor/bin/phpcb --log $(BUILD)/logs --source lib --output $(BUILD)/code-browser

clean:
	-rm -rf $(BUILD)
	-rm -rf $(DIST)

distclean: clean
	-rm -rf vendor

.PHONY: test init dist analyze dependencies mess cpd sniff docs browse clean distclean
