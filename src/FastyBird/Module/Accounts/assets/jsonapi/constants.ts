// jsona does not export this from its package root, only from the
// jsona/lib/simplePropertyMappers subpath, which 1.13's exports map blocks.
// It is a stable string literal, so declaring it locally removes the only
// reason this repository could not move off jsona ~1.12.
export const RELATIONSHIP_NAMES_PROP = 'relationshipNames';
