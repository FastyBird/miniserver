#!/usr/bin/env python3
"""E5.1 census (#633): condense t1.py's report into one pipe-separated row per type.

  python3 tools/census/e5/t1rows.py /tmp/e633/t1.txt

Columns: type | production implementers | test implementers | Core src files | Core test files |
outside src files | outside test files | DI lines outside tests | NEON/Latte/XML/JSON/YAML lines
"""
import re
import sys

rows = []
cur = None
for line in open(sys.argv[1]):
    m = re.match(r'## (\S+)', line)
    if m:
        cur = {'n': m.group(1), 'di': []}
        rows.append(cur)
        continue
    if cur is None:
        continue
    m = re.match(r'  implementers (prod|test) \((\d+)\): (.*)', line)
    if m:
        cur[m.group(1)] = m.group(3).strip()
        continue
    m = re.match(r'  files (\S+)\s+(\d+)', line)
    if m:
        cur[m.group(1)] = m.group(2)
        continue
    m = re.match(r'    (\S+?):(\d+): ', line)
    if m and '/tests/' not in m.group(1):
        cur['di'].append(m.group(1).replace('src/FastyBird/', '') + ':' + m.group(2))
    m = re.match(r'  NEON\S* \((\d+)\)', line)
    if m:
        cur['neon'] = m.group(1)

for r in rows:
    print('|'.join([
        r['n'].replace('FastyBird\\Core\\', ''),
        r['prod'].replace('FastyBird\\Core\\', ''),
        r['test'].replace('FastyBird\\Core\\Tests\\Fixtures\\Dummy\\', '').replace(
            'class@anonymous:src/FastyBird/Core/Core/tests/cases/unit/', 'anonymous class in '),
        r['core-src'], r['core-tests'], r['outside-src'], r['outside-tests'],
        ', '.join(r['di']) or '-', r.get('neon', '0'),
    ]))
