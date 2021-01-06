#!/usr/bin/env bash

rm -Rf "$(pwd)"/src/images

mkdir "$(pwd)"/src/images
mkdir "$(pwd)"/src/images/methods

cp -Rf "$(pwd)"/vendor/ccv/images/*.png "$(pwd)"/src/images
cp -Rf "$(pwd)"/vendor/ccv/images/methods/*.png "$(pwd)"/src/images/methods
cd "$(pwd)"/src/images/methods;
mogrify -resize 999x32 *.png
cd ../../../
