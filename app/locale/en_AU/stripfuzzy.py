import os, polib
for dirname, dirnames, filenames in os.walk('./'):
    for filename in filenames:
        try: ext = filename.rsplit('.', 1)[1]
        except: ext = ''
        if ext == 'po':
            po = polib.pofile(filename)
            for entry in po.fuzzy_entries():
                entry.msgstr = ''
                if entry.msgid_plural: entry.msgstr_plural['0'] = ''
                if entry.msgid_plural and '1' in entry.msgstr_plural: entry.msgstr_plural['1'] = ''
                if entry.msgid_plural and '2' in entry.msgstr_plural: entry.msgstr_plural['2'] = ''
                entry.flags.remove('fuzzy')
            po.save()
