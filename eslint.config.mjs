import js from '@eslint/js';
import svelte from 'eslint-plugin-svelte';
import globals from 'globals';
import ts from 'typescript-eslint';

export default [
	js.configs.recommended,
	...ts.configs.recommended,
	...svelte.configs.recommended,
	{
		languageOptions: { globals: { ...globals.browser, wp: true } },
		rules: {
			'no-unused-vars': 'off',
			'@typescript-eslint/no-unused-vars': [
				'error',
				{
					argsIgnorePattern: '^_',
					varsIgnorePattern: '^_',
					caughtErrorsIgnorePattern: '^_',
				},
			],
		},
	},
	{
		files: ['**/*.svelte', '**/*.svelte.{js,mjs,cjs,ts}'],
		languageOptions: {
			parser: svelte.parser,
			parserOptions: {
				parser: {
					ts: ts.parser,
					js: js.parser,
				},
			},
		},
	},
	{
		files: ['**/*.cjs'],
		languageOptions: { globals: globals.node },
		rules: {
			'@typescript-eslint/no-require-imports': 'off',
		},
	},
	{
		ignores: [
			'**/build/**',
			'**/dist/**',
			'coverage/',
			'src/plugin-src/**',
		],
	},
];
