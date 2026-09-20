# FastyBird MiniServer Core

The MiniServer's core package: application bootstrap, DI container wiring, JSON:API
document handling, authentication and authorization (SimpleAuth), the Doctrine
CRUD/query/timestampable helpers, the exchange (pub/sub) bus, phone number
validation, PSR-7 routing (SlimRouter), the WebSockets/WAMP protocol layer, and the
HTTP and WS server runtimes.

This package replaces `Core/Application`, `Core/Exchange`, `Core/SimpleAuth`,
`Core/Tools`, `Library/DateTimeFactory`, `Library/DoctrineCrud`,
`Library/DoctrineOrmQuery`, `Library/DoctrineTimestampable`, `Library/JsonApi`,
`Library/Metadata`, `Library/Phone`, `Library/SlimRouter`, `Library/WebSockets`,
`Plugin/WebServer` and `Plugin/WsServer`. See
`docs/superpowers/specs/2026-09-20-core-consolidation-design.md` for the full
rationale and namespace mapping.

Every other extension in this repository depends on this package
(`fastybird/miniserver-core`) instead of picking individual packages.
