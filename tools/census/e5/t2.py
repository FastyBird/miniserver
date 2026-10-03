#!/usr/bin/env python3
"""E5.1 census (#633), table T2: dead-code evidence.

For every class-like type declared under Core's src/, counts the files that reference it by
FQCN (code and docblock, resolved by refs.php through php-parser + NameResolver), excluding its
own file and excluding tools/ (the move maps name every type). Prints:

  1. every Core type with no reference outside its own file;
  2. for each named candidate (argv[2:] or the built-in Epic section 1.12 list): the
     referencing files, PHP string literals naming it, and NEON/Latte/XML/JSON lines naming
     its FQCN or (for the special cases) the strings it is reachable through.

Usage: python3 tools/census/e5/t2.py /tmp/e633/refs.json > /tmp/e633/t2.txt
"""
import json
import re
import subprocess
import sys
from collections import defaultdict

CORE = 'src/FastyBird/Core/Core/'
d = json.load(open(sys.argv[1]))
decls = d['decls']

refs = defaultdict(set)
for fq, f, line, how in d['refs']:
    if not f.startswith('tools/'):
        refs[fq].add(f)

strings = defaultdict(list)
for s, f, line in d['strings']:
    if not f.startswith('tools/'):
        strings[s.lstrip('\\').split('::')[0]].append('%s:%d' % (f, line))

core_types = sorted(n for n, x in decls.items() if x['file'].startswith(CORE + 'src/') and not x['anonymous'])
print('# 1. Core types (%d declared under %ssrc) with no reference outside their own file (tools/ excluded)'
      % (len(core_types), CORE))
unref = []
for n in core_types:
    others = refs[n] - {decls[n]['file']}
    if not others and not strings.get(n):
        unref.append(n)
        print('  %s  (%s)' % (n, decls[n]['file'][len(CORE):]))
print('  total: %d' % len(unref))

cands = sys.argv[2:] or [
    'FastyBird\\Core\\Caching\\MemoryAdapterStorage',
    'FastyBird\\Core\\WebSockets\\Exceptions\\WampNotImplemented',
    'FastyBird\\Core\\WebSockets\\Helpers\\Formatter\\Symfony',
    'FastyBird\\Core\\WebSockets\\Helpers\\Formatter\\IFormatter',
    'FastyBird\\Core\\Http\\ScalarEntity',
    'FastyBird\\Core\\Security\\Latte\\AccessExtension',
    'FastyBird\\Core\\WebSockets\\PushMessages\\Consumer',
    'FastyBird\\Core\\WebSockets\\PushMessages\\Pusher',
    'FastyBird\\Core\\WebSockets\\PushMessages\\IConsumer',
    'FastyBird\\Core\\WebSockets\\PushMessages\\IConsumersRegistry',
    'FastyBird\\Core\\WebSockets\\PushMessages\\IPusher',
    'FastyBird\\Core\\WebSockets\\PushMessages\\ConsumersRegistry',
    'FastyBird\\Core\\WebSockets\\Subscribers\\OnServerStartHandler',
    'FastyBird\\Core\\Persistence\\Crud\\EntityCrudFactory',
    'FastyBird\\Core\\Presenters\\DefaultPresenter',
    'FastyBird\\Core\\Http\\Routing\\Handlers\\RequestResponseArgsHandler',
    'FastyBird\\Core\\Values\\Transformers\\DataTypeTransformer',
    'FastyBird\\Core\\Persistence\\Types\\UTCDateTime',
    'Nette\\Security\\User',
    'FastyBird\\Core\\Persistence\\Entities\\IEntityRemoved',
    'FastyBird\\Core\\Persistence\\Entities\\TEntityRemoved',
    'FastyBird\\Core\\Phone\\Entities\\TPhone',
]
other_files = subprocess.run(['git', 'ls-files', '--', '*.neon', '*.latte', '*.xml', '*.json', '*.yaml', '*.yml'],
                             capture_output=True, text=True).stdout.split()


def grep_other(needle):
    hits = []
    for f in other_files:
        if 'phpstan-baseline' in f or f.startswith('tools/'):
            continue
        try:
            for i, l in enumerate(open(f, encoding='utf-8', errors='replace'), 1):
                if needle in l or needle.replace('\\', '\\\\') in l:
                    hits.append('%s:%d' % (f, i))
        except OSError:
            pass
    return hits


print()
print('# 2. Candidates')
for n in cands:
    x = decls.get(n)
    own = x['file'] if x else None
    print()
    print('## %s  %s' % (n, ('(%s %s)' % (x['kind'], own)) if x else '(not declared)'))
    if x:
        print('  final=%s abstract=%s extends=%s implements=%s' % (x['final'], x['abstract'], x['extends'], x['implements']))
        subs = [m for m, y in decls.items() if n in y['extends'] or n in y['implements'] or n in y['traits']]
        print('  direct subtypes/users: %s' % (', '.join(subs) or '-'))
    others = sorted(refs[n] - {own})
    print('  referencing files (%d): %s' % (len(others), ' '.join(others) or '-'))
    print('  PHP strings (%d): %s' % (len(strings.get(n, [])), ' '.join(strings.get(n, [])) or '-'))
    hits = grep_other(n)
    print('  NEON/Latte/XML/JSON/YAML (%d): %s' % (len(hits), ' '.join(hits) or '-'))
