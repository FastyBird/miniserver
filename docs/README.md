# FastyBird MiniServer documentation

- [architecture.md](./architecture.md) -- extension inventory, request routing, the DI config load order and where each extension's own `docs/` fits in
- [configuration.md](./configuration.md) -- `local.neon` snippets for every extension that ships in the tree but is not registered by default
- [deployment.md](./deployment.md) -- Docker images, the supervisor program layout, and production environment defaults

Each extension under `src/FastyBird/<Type>/<Name>/` keeps its own `README.md` and `docs/` for extension-specific detail (device protocols, entity schemas, and so on). This directory only holds the application-level documentation that spans extensions.
