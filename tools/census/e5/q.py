#!/usr/bin/env python3
"""E5.1 census (#633): ad-hoc query over the refs.php index.

  python3 tools/census/e5/q.py /tmp/e633/refs.json <regex over FQCN> [--lines]

For every declared type whose FQCN matches, prints kind/final/abstract, its subtypes, and the
files (and with --lines, file:line:how) that reference it by FQCN outside its own file;
tools/ is excluded.
"""
import json
import re
import sys
from collections import defaultdict

d = json.load(open(sys.argv[1]))
rx = re.compile(sys.argv[2])
lines = '--lines' in sys.argv
r = defaultdict(list)
for fq, f, l, h in d['refs']:
    if not f.startswith('tools/'):
        r[fq].append((f, l, h))
C = 'src/FastyBird/Core/Core/'
for n in sorted(d['decls']):
    x = d['decls'][n]
    if not rx.search(n):
        continue
    subs = [m for m, y in d['decls'].items() if n in y['extends'] + y['implements'] + y['traits']]
    hits = [(f, l, h) for f, l, h in r[n] if f != x['file']]
    files = sorted({f for f, l, h in hits})
    print('%s [%s%s%s] subs=%s' % (n, x['kind'], ' final' if x['final'] else '', ' abstract' if x['abstract'] else '',
                                    [s.replace(C, 'C:') for s in subs]))
    if lines:
        for f, l, h in sorted(hits):
            print('    %s:%d %s' % (f.replace(C, 'C:'), l, h))
    else:
        print('    %d files: %s' % (len(files), ' '.join(f.replace(C, 'C:') for f in files)))
