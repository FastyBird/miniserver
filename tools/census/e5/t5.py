#!/usr/bin/env python3
"""E5.1 census (#633), table T5: consumers of each FastyBird\\Core\\Constants constant.

  python3 tools/census/e5/t5.py /tmp/e633/constvals.txt /tmp/e633/consts.txt

constvals.txt: `members.php constants` (Reflection, name<TAB>value); consts.txt: `consts.php`
(php-parser, every resolved Constants::X fetch). Prints, per constant: the number of fetches,
and the consumers grouped as Core capability (src or tests) / extension (src or tests) /
root (public, tests, bin, tools). NEON/Latte/JSON mentions are grepped separately and printed.
"""
import collections
import re
import subprocess
import sys

vals = [l.rstrip('\n').split('\t', 1) for l in open(sys.argv[1])]
fetch = collections.defaultdict(list)
for l in open(sys.argv[2]):
    k, loc = l.rstrip('\n').split('\t')
    fetch[k.split('::')[1]].append(loc.rsplit(':', 1)[0])


def owner(f):
    m = re.match(r'src/FastyBird/Core/Core/(src|tests)/(?:cases/unit/)?([^/]+)', f)
    if m:
        return 'Core:%s%s' % (m.group(2).replace('.php', ''), '(t)' if m.group(1) == 'tests' else '')
    m = re.match(r'src/FastyBird/([^/]+)/([^/]+)/(src|tests)/', f)
    if m:
        return '%s%s' % (m.group(2), '(t)' if m.group(3) == 'tests' else '')
    return 'root:' + f


other = subprocess.run(['git', 'grep', '-n', 'Constants::', '--', '*.neon', '*.latte', '*.json', '*.xml'],
                       capture_output=True, text=True).stdout
print('NEON/Latte/JSON/XML lines naming Constants:: :', other.count('\n'))
print(other)
for name, val in vals:
    fs = fetch.get(name, [])
    groups = collections.Counter(owner(f) for f in set(fs))
    print('%s\t%d fetches, %d files\t%s' % (name, len(fs), len(set(fs)),
                                            ', '.join('%s %d' % (k, v) for k, v in sorted(groups.items()))))
