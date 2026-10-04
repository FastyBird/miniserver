#!/usr/bin/env python3
"""E5.1 census (#633), table T1: the 50 prefixed Core types.

Reads the reference index written by refs.php (parsed with nikic/php-parser, names resolved
through each file's imports, so IDriver/IStorage/IMessage are counted per FQCN) and prints, per
interface/trait:

  - production and test implementers (transitive: through extends chains of classes and
    interfaces), anonymous classes included;
  - references by FQCN, as distinct files, split core-src / core-tests / outside-src /
    outside-tests, and the outside files listed;
  - lines that hand the type to the DI container (getByType, getDefinitionByType, findByType,
    setType, setImplement, addFactoryDefinition ... on the same line as a resolved reference);
  - PHP string literals and NEON/Latte lines naming the FQCN (grep over tracked files).

Usage (host, python3 >= 3.9):
  python3 tools/census/e5/t1.py /tmp/e633/refs.json > /tmp/e633/t1.txt
"""
import json
import re
import subprocess
import sys
from collections import defaultdict

CORE = 'src/FastyBird/Core/Core/'

d = json.load(open(sys.argv[1]))
decls = d['decls']
refs = d['refs']


def group(f):
    if f.startswith(CORE + 'src/'):
        return 'core-src'
    if f.startswith(CORE):
        return 'core-tests'
    if '/tests/' in f or f.startswith('tests/'):
        return 'outside-tests'
    return 'outside-src'


# ancestors (classes + interfaces) per declared type, transitive
def ancestors(name, seen=None):
    seen = seen if seen is not None else set()
    dd = decls.get(name)
    if dd is None:
        return seen
    for p in dd['extends'] + dd['implements']:
        if p not in seen:
            seen.add(p)
            ancestors(p, seen)
    return seen


anc = {n: ancestors(n) for n in decls}

prefixed = sorted(
    n for n, dd in decls.items()
    if n.startswith('FastyBird\\Core\\') and dd['file'].startswith(CORE + 'src/')
    and dd['kind'] in ('interface', 'trait') and re.match(r'^[IT][A-Z]', n.rsplit('\\', 1)[1])
)

refs_by = defaultdict(list)
for fq, f, line, how in refs:
    refs_by[fq].append((f, line, how))

# DI-ish lines: file:line -> text
lines_cache = {}


def line_text(f, n):
    if f not in lines_cache:
        try:
            lines_cache[f] = open(f, encoding='utf-8', errors='replace').read().split('\n')
        except OSError:
            lines_cache[f] = []
    ls = lines_cache[f]
    return ls[n - 1] if 0 < n <= len(ls) else ''


DI_RE = re.compile(r'getByType|getDefinitionByType|findByType|setType|setImplement|addFactoryDefinition|'
                   r'addAccessorDefinition|addLocatorDefinition|->getService|->createInstance|setFactory')

tracked_other = subprocess.run(['git', 'ls-files', '--', '*.neon', '*.latte', '*.xml', '*.json', '*.yaml', '*.yml'],
                               capture_output=True, text=True).stdout.split()

print('# T1 -- %d prefixed types (interface I*/trait T* declared under %ssrc)' % (len(prefixed), CORE))
for name in prefixed:
    dd = decls[name]
    impl_prod, impl_test = [], []
    for n, a in anc.items():
        if name in a or (dd['kind'] == 'trait' and name in decls[n]['traits']):
            (impl_test if ('/tests/' in decls[n]['file'] or decls[n]['file'].startswith('tests/')) else impl_prod).append(
                n + (' [interface]' if decls[n]['kind'] == 'interface' else '')
                + (' [abstract]' if decls[n]['abstract'] else '') + ('' if decls[n]['final'] or decls[n]['kind'] != 'class' else ' [not final]'))
    files = defaultdict(set)
    for f, line, how in refs_by.get(name, []):
        if f == dd['file']:
            continue
        files[group(f)].add(f)
    di = sorted({'%s:%d: %s' % (f, line, line_text(f, line).strip()) for f, line, how in refs_by.get(name, [])
                 if DI_RE.search(line_text(f, line))})
    strs = [s for s in d['strings'] if s[0].lstrip('\\') == name or s[0].lstrip('\\').startswith(name + '::')]
    esc = name.replace('\\', '\\\\')
    other = []
    for f in tracked_other:
        if 'phpstan-baseline' in f:
            continue
        try:
            txt = open(f, encoding='utf-8', errors='replace').read()
        except OSError:
            continue
        for i, l in enumerate(txt.split('\n'), 1):
            if re.search(re.escape(name) + r'(?![A-Za-z0-9_])', l) or re.search(re.escape(esc) + r'(?![A-Za-z0-9_])', l):
                other.append('%s:%d' % (f, i))
    print()
    print('## %s  (%s, %s:%d)' % (name, dd['kind'], dd['file'], dd['line']))
    print('  implementers prod (%d): %s' % (len(impl_prod), ', '.join(sorted(impl_prod)) or '-'))
    print('  implementers test (%d): %s' % (len(impl_test), ', '.join(sorted(impl_test)) or '-'))
    for g in ('core-src', 'core-tests', 'outside-src', 'outside-tests'):
        print('  files %-13s %d' % (g, len(files[g])))
    out = sorted(files['outside-src'] | files['outside-tests'])
    if out:
        print('  outside files: ' + ' '.join(out))
    print('  DI lines (%d):' % len(di))
    for x in di:
        print('    ' + x)
    print('  PHP strings (%d): %s' % (len(strs), ' '.join('%s:%d' % (s[1], s[2]) for s in strs)))
    print('  NEON/Latte/XML/JSON/YAML (%d): %s' % (len(other), ' '.join(other)))
