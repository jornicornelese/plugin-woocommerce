#!/bin/bash

#Requires gettext library. (sudo apt install gettext)

# shellcheck disable=SC2044
for file in $(find ./src/i18n/ -name "*.po")
do
  msgfmt -o ${file/.po/.mo} $file
done