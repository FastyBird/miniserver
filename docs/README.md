# FastyBird MiniServer documentation

- [architecture.md](./architecture.md) -- extension inventory, Core's capability layout and composite DI extension, dependency layering, request routing, the DI config load order, runtime processes, the frontend and the database schema
- [configuration.md](./configuration.md) -- every `fbCore` key, and `local.neon` snippets for every extension that ships in the tree but is not registered by default
- [deployment.md](./deployment.md) -- Docker images, the supervisor program layout, and production environment defaults
- [conventions.md](./conventions.md) -- the identity, import, namespace, DI, event, docblock, naming and code rules, and which gate enforces each
- [baseline.md](./baseline.md) -- how to reproduce each gate, the proof tools, and the known conditions of the toolchain
- [deprecations.md](./deprecations.md) -- dependencies that are unmaintained or on their way out, and what each costs

Each extension under `src/FastyBird/<Type>/<Name>/` keeps its own `README.md` and `docs/` for extension-specific detail (device protocols, entity schemas, and so on). This directory only holds the application-level documentation that spans extensions.

[superpowers/](./superpowers/) is the archive of the specs and plans of the repository merge and of the Core refactor: dated records of how the system got here, not a description of it.
