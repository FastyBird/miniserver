#!/usr/bin/env python3
"""E5.1 census (#633): facts read from compiled DI containers.

Input: a directory written by `php tools/di-snapshot.php <dir>` (one JSON per container; the
"wiring" key is the generated container's own $wiring, i.e. what getByType()/findByType()
return at runtime, and "setup" is each definition's setup list in compiled order).

  python3 tools/census/e5/di.py <snapshotDir> hooks
      per container: the setup statements on the services that carry the WebSockets hooks
      (ServerRuntime, Wrapper, WampApplication) in order, abbreviated
  python3 tools/census/e5/di.py <snapshotDir> wiring <regex>
      per container and per matching type: the autowiring candidates
      ($wiring[type][0] = autowired, [1] = findByType-only)
  python3 tools/census/e5/di.py <snapshotDir> services <regex>
      per container: the services whose resolved type matches
"""
import json
import os
import re
import sys

snap, mode = sys.argv[1], sys.argv[2]
idx = json.load(open(os.path.join(snap, 'index.json')))
files = idx.get('containers', {})
containers = sorted(files.items()) if files else []
if not containers:
    for f in sorted(os.listdir(snap)):
        if f.endswith('.json') and f != 'index.json':
            containers.append((f[:-5], f))


def load(f):
    if isinstance(f, dict):
        f = f.get('file')
    return json.load(open(os.path.join(snap, f)))


HOOK_TYPES = ('FastyBird\\Core\\WebSockets\\Server\\ServerRuntime', 'FastyBird\\Core\\WebSockets\\Server\\Wrapper',
              'FastyBird\\Core\\WebSockets\\Controllers\\WampApplication')


def short(setup):
    e = setup['entity'] if isinstance(setup['entity'], str) else json.dumps(setup['entity'])
    args = []
    for a in setup.get('arguments', []):
        if isinstance(a, dict) and 'literal' in a:
            args.append(a['literal'].rsplit('\\', 1)[-1])
        elif isinstance(a, dict) and '@' in a:
            if a['@'] not in ('self',) and 'dispatcher' not in a['@']:
                args.append('@' + a['@'])
        elif isinstance(a, str):
            args.append(a.replace('FastyBird\\', ''))
    m = re.match(r'\$?\??\$?(?:service)?->(on\w+)\[\] = (.*)', e)
    if m:
        kind = 'dispatch' if 'dispatch' in m.group(2) else ('enable' if 'enable' in m.group(2) else 'callable')
        return '%s <- %s(%s)' % (m.group(1), kind, ', '.join(args))
    return e[:60] + ('(' + ', '.join(args) + ')' if args else '')


groups = {}

for cid, f in containers:
    d = load(f)
    if not d.get('compiled', True):
        print('== %s: NOT COMPILED' % cid)
        continue
    svcs = d['services']
    if mode == 'hookgroups':
        # identical setup sequences per hook service, grouped across containers
        for name, s in svcs.items():
            if s.get('type') in HOOK_TYPES:
                seq = tuple(re.sub(r'@[\w.]*[Dd]ispatcher\w*, |@\d+, ', '', short(st)) for st in s.get('setup', []))
                groups.setdefault((s['type'].rsplit('\\', 1)[-1], seq), []).append(cid)
        continue
    if mode == 'hooks':
        print('== %s' % cid)
        for name, s in svcs.items():
            if s.get('type') in HOOK_TYPES:
                print('  %s (%s)' % (name, s['type'].rsplit('\\', 1)[-1]))
                for i, st in enumerate(s.get('setup', []), 1):
                    print('    %2d. %s' % (i, short(st)))
    elif mode == 'wiring':
        rx = re.compile(sys.argv[3])
        out = []
        for t, w in d['wiring'].items():
            if rx.search(t):
                out.append('  %s: %s' % (t, json.dumps(w)))
        print('== %s' % cid)
        print('\n'.join(out) if out else '  (none)')
    elif mode == 'services':
        rx = re.compile(sys.argv[3])
        out = ['  %s: %s autowired=%s' % (n, s.get('type'), s.get('autowired')) for n, s in svcs.items()
               if s.get('type') and rx.search(s['type'])]
        print('== %s' % cid)
        print('\n'.join(out) if out else '  (none)')

if mode == 'hookgroups':
    for (t, seq), cids in sorted(groups.items()):
        print('== %s, %d container(s): %s' % (t, len(cids), ' '.join(cids)))
        for i, x in enumerate(seq, 1):
            print('    %2d. %s' % (i, x))

if mode == 'reach':
    # reach <regexFrom> <regexTo>: in every container, is there a reference path (constructor
    # arguments, setup arguments and factory, transitively) from a service whose type matches
    # regexFrom to one whose type matches regexTo? Prints the first path found, or "no path".
    rf, rt = re.compile(sys.argv[3]), re.compile(sys.argv[4])

    def refs_of(v):
        if isinstance(v, dict):
            if '@' in v and isinstance(v['@'], str):
                yield v['@']
            for x in v.values():
                yield from refs_of(x)
        elif isinstance(v, list):
            for x in v:
                yield from refs_of(x)

    for cid, f in containers:
        d = load(f)
        if not d.get('compiled', True):
            continue
        svcs = d['services']
        alias = d.get('aliases', {})
        graph = {}
        for n, s in svcs.items():
            graph[n] = {alias.get(r, r) for r in refs_of([s.get('arguments', []), s.get('setup', []), s.get('factory')])
                        if alias.get(r, r) in svcs and alias.get(r, r) != n}
        starts = [n for n, s in svcs.items() if s.get('type') and rf.search(s['type'])]
        found = None
        for st in starts:
            seen, stack = {st: None}, [st]
            while stack and not found:
                cur = stack.pop()
                for nx in graph.get(cur, ()):
                    if nx in seen:
                        continue
                    seen[nx] = cur
                    if svcs[nx].get('type') and rt.search(svcs[nx]['type']):
                        path, p = [nx], cur
                        while p is not None:
                            path.append(p)
                            p = seen[p]
                        found = ' -> '.join(reversed(path))
                        break
                    stack.append(nx)
        print('== %s: %s' % (cid, found or 'no path (from %d start services)' % len(starts)))
