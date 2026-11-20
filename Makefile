help:
	@echo "Please use \`make <target>' where <target> is one of"
	@echo "  tag            to modify the version and tag"

tag:
	$(if $(TAG),,$(error TAG is not defined. Pass via "make tag TAG=1.22.0"))
	@echo Tagging $(TAG)
	sed -i '' -e "s/APP_VERSION = '.*'/APP_VERSION = '$(TAG)'/" src/Console/AppFactory.php
	php -l src/Console/AppFactory.php
	git add -A
	git commit -m '$(TAG) release' -n
	git tag -s '$(TAG)' -m 'Version $(TAG)'
