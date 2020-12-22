#! /bin/bash
echo "This script will extract messages and append to messages.pot "
echo "and then it will apply found changes to all po files in subdirs."
echo "Finally it will update mo files"
cd "$(dirname "$0")/../../app/locale"
find ../.. -iname "*.php"  | grep -v "../../assets" | grep -v "../../vendor"   | grep -v "../../app/tmp" |xgettext --files-from=- --language=PHP --from-code=UTF-8  --add-comments=TRANSLATORS: -k_t  -k_p -k_  -o messages.pot --copyright-holder="Whirl-I-Gig 2020"  --msgid-bugs-address=info@collectiveaccess.org --package-name=Providence
find ../.. -iname "*.conf" | grep -v "../../assets" | grep -v "../../vendor" | grep -v "../../app/conf/app.conf" | xgettext --files-from=- --language=Lua -k -k_  -j -o  messages.pot --copyright-holder="Whirl-I-Gig 2020"  --msgid-bugs-address=info@collectiveaccess.org --package-name=Providence
find . -type d -iname "*_*" -exec bash -c 'msgmerge --update "{}/messages.po" messages.pot' \;
find . -type d -iname "*_*" -exec bash -c 'msgfmt "{}/messages.po" -o "{}/messages.mo"  --statistics --verbose' \;
cd -
