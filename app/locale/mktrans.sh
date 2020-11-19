#! /bin/bash
echo "This script will extract messages and append to messages.pot "
echo "and then it will apply found changes to all po files in subdirs."
echo "Finally it will update mo files"
find ../.. -iname "*.php"  | grep -v "../../assets" | grep -v "../../vendor"   | grep -v "../../app/tmp" |xgettext --files-from=- --language=PHP --from-code=UTF-8  --add-comments=TRANSLATORS: -k_t  -k_p -k_  -o messages.pot
find ../.. -iname "*.conf" | grep -v "../../assets" | grep -v "../../vendor" | grep -v "../../app/conf/app.conf" | xgettext --files-from=- --language=Lua -k -k_  -j -o  messages.pot
find . -type f -iname "*.po" -exec bash -c 'msgmerge --update "{}" messages.pot' \;
find . -type f -iname "*.po" -exec bash -c 'msgfmt "{}" -o "$(basename "{}" .po).mo" --statistics --verbose' \;
