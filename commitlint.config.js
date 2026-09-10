// Enforces conventional commits on every local commit (via the husky commit-msg hook).
// Shares the same type and scope vocabulary as .github/workflows/lint-pr.yml and
// CONTRIBUTING.md -- keep all three in sync when a scope is added or removed.
// The built-in subject-case rule is disabled in favour of a custom rule that only
// checks the first character, mirroring SmartPanel's commitlint.config.js and
// .github/workflows/lint-pr.yml's first-character-only check -- commit subjects
// routinely contain embedded uppercase (filenames, acronyms, proper nouns).
module.exports = {
	extends: ['@commitlint/config-conventional'],
	plugins: [
		{
			rules: {
				'subject-first-char-lowercase': (parsed) => {
					const subject = parsed.subject || '';

					if (subject.length === 0) {
						return [true];
					}

					const firstChar = subject[0];

					return [
						firstChar === firstChar.toLowerCase(),
						'subject must start with a lowercase character',
					];
				},
			},
		},
	],
	rules: {
		'type-enum': [
			2,
			'always',
			['feat', 'fix', 'docs', 'style', 'refactor', 'test', 'chore', 'perf', 'ci', 'build', 'revert'],
		],
		'scope-enum': [
			2,
			'always',
			[
				'core',
				'module',
				'connector',
				'plugin',
				'bridge',
				'addon',
				'automator',
				'library',
				'ui',
				'infra',
				'ci',
				'deps',
				'docs',
				'cross',
			],
		],
		'scope-empty': [2, 'never'],
		'subject-case': [0],
		'subject-first-char-lowercase': [2, 'always'],
		'subject-empty': [2, 'never'],
		'subject-full-stop': [2, 'never', '.'],
	},
};
