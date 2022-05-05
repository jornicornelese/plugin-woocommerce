#!/bin/bash

# Cleanup any leftovers
rm -f ./biller-business-invoice.zip
rm -fR ./deploy

# Create deployment source
echo "Copying plugin source..."
mkdir ./deploy
cp -R ./src ./deploy/biller-business-invoice

# Ensure proper composer dependencies
echo "Installing composer dependencies..."
rm -fR ./deploy/biller-business-invoice/vendor
composer install --no-dev --working-dir=$PWD/deploy/biller-business-invoice/

# Remove unnecessary files from final release archive
cd deploy
echo "Removing unnecessary files from final release archive..."
rm -rf biller-business-invoice/tests
rm -rf biller-business-invoice/vendor/biller/integration-core/tests

# Create plugin archive
echo "Creating new archive..."
zip -q -r biller-business-invoice.zip biller-business-invoice

cd ../
if [ ! -d ./dist/ ]; then
        mkdir ./dist/
fi

version="$1"
if [ "$version" != "" ]; then
    if [ ! -d ./dist/"$version"/ ]; then
        mkdir ./dist/"$version"/
    fi

    mv ./deploy/biller-business-invoice.zip ./dist/${version}/
    touch "./dist/$version/Release notes $version.txt"
    echo "New release created under: $PWD/dist/$version"
else
    if [ ! -d ./dist/dev/ ]; then
        mkdir ./dist/dev/
    fi
    mv ./deploy/biller-business-invoice.zip ./dist/dev/
    echo "New plugin archive created: $PWD/dist/dev/biller-woocommerce.zip"
fi

rm -fR ./deploy
