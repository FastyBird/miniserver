# Debian packaging (unsupported)

These files are ported from the old `FastyBird/miniserver` repository, per
decision D7 of the merge design: Docker is the only supported deployment
target for this merge. This packaging is kept for a possible later
appliance-style install path and has not been re-validated against the
merged repository.

Fixed during the port:

- `DEBIAN/postinst` originally symlinked
  `/usr/lib/miniserver/vendor/fastybird/bootstrap/bin/fb-console`, a package that does
  not exist in this repository. Repointed at `bin/fb-console.php`, the only console
  entry point there is. Do not "correct" it to `vendor/bin/fb-console`: a cold install
  on 2026-09-10 confirmed that path is never created, because Composer does not link a
  root package's own `bin` entries into `vendor/bin/`.
- `etc/systemd/system/fb-miniserver.service` and `DEBIAN/control` originally depended on
  `php8.1`. This repository requires PHP 8.4, so both were bumped to `php8.4`.

Do not run `make_deb.sh` against a production host until this packaging has
been installed and tested end to end against the merged repository.
