#!/usr/bin/env bash

composer install
bash copyImages.sh

rm -Rf "$(pwd)"/build
mkdir "$(pwd)"/build
mkdir "$(pwd)"/build/ccvonlinepayments

cp -LR "$(pwd)"/src/* "$(pwd)"/build/ccvonlinepayments
cp -LR "$(pwd)"/vendor "$(pwd)"/build/ccvonlinepayments
rm -Rf "$(pwd)"/build/ccvonlinepayments/vendor/ccvonlinepayments/images

cd "$(pwd)"/build/ || exit
zip -9 -r ccvonlinepayments.zip ccvonlinepayments
cd ../
