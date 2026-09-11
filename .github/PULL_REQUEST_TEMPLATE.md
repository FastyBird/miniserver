## What does this change?

<!-- A short description of the change and why it is needed. -->

## Related issues

<!-- e.g. Closes #123 -->

## Type of change

- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation
- [ ] Maintenance / tooling

## Checklist

- [ ] **The pull request title follows `<type>(<scope>): <subject>`.**
      `lint-pr.yml` enforces this and the check fails otherwise. Type is one of
      `feat fix docs style refactor test chore perf ci build revert`; scope is one of
      `core module connector plugin bridge addon automator library ui infra ci deps
      deps-dev docs cross`. The subject starts lowercase and has no trailing period.
- [ ] Commit messages follow the same convention (`commitlint` enforces this locally
      once `yarn install` has run and wired the husky hook).
- [ ] I ran the relevant gates and they pass.

## Verification

<!--
Run gates in containers, not on the host. The toolchain is PHP 8.2 / Node 20 and results
from a newer host toolchain are not evidence. See CONTRIBUTING.md.

  make lint · make cs · make phpstan · make tests
  yarn build · yarn types · yarn lint:js · yarn lint:styles · yarn pretty:check

Paste the output that matters, or say which gates you ran and which you did not.
-->

- [ ] PHP gates (`lint`, `cs`, `phpstan`, `tests`)
- [ ] Frontend gates (`build`, `types`, `lint:js`, `lint:styles`, `pretty:check`)
- [ ] Not applicable — this change touches neither
