#!/usr/bin/env bash

composer install
bash copyImages.sh

PACKAGE_VERSION=$(cat composer.json \
  | grep version \
  | head -1 \
  | awk -F: '{ print $2 }' \
  | sed 's/[", ]//g')

rm -Rf "$(pwd)"/build
mkdir "$(pwd)"/build
mkdir "$(pwd)"/build/ccvonlinepayments

cp -LR "$(pwd)"/src/* "$(pwd)"/build/ccvonlinepayments
cp -LR "$(pwd)"/vendor "$(pwd)"/build/ccvonlinepayments
rm -Rf "$(pwd)"/build/ccvonlinepayments/vendor/ccv/php-lib/vendor
rm -Rf "$(pwd)"/build/ccvonlinepayments/vendor/ccv/images/vendor
rm -Rf "$(pwd)"/build/ccvonlinepayments/vendor/ccvonlinepayments/images

cd "$(pwd)"/build/ || exit
zip -9 -r "ccvonlinepayments-woocommerce-$PACKAGE_VERSION.zip" ccvonlinepayments
cd ../
