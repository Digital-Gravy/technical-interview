/**
 * DynamicContentProcessor — resolves dynamic expressions against data sources.
 *
 * Expressions use the syntax {source.path.to.value} and are resolved against
 * a list of sources. Each source has a key (e.g. "this") and a data object.
 *
 * Example:
 *   sources: [{ key: 'this', source: { title: 'Hello World' } }]
 *   input:   "Title: {this.title}"
 *   output:  "Title: Hello World"
 */

import { applyModifier, isModifier } from './modifiers';

export type Source = { key: string; source: Record<string, unknown> };

/**
 * Replace all {expression} templates in a string with resolved values.
 */
export function replaceTemplates(value: string, sources: Source[]): string {
	return value.replace(/\{([^}]+)\}/g, (_, expression: string) => {
		const resolved = resolveExpression(expression, sources);

		if (resolved === null || resolved === undefined) {
			return '';
		}

		if (typeof resolved === 'object') {
			return JSON.stringify(resolved);
		}

		if (typeof resolved === 'boolean') {
			return resolved ? 'true' : 'false';
		}

		return String(resolved);
	});
}

/**
 * Resolve a single expression against the given sources.
 *
 * The first part of the expression matches a source key. Remaining parts
 * traverse into the source data. Sources are searched in reverse order
 * (last source wins when keys conflict).
 */
export function resolveExpression(
	expression: string,
	sources: Source[],
): unknown {
	const parts = splitExpression(expression);

	if (parts.length === 0) {
		return undefined;
	}

	const sourceKey = parts[0];

	// Search sources in reverse order — last source wins.
	let value: unknown = undefined;
	let found = false;
	for (let i = sources.length - 1; i >= 0; i--) {
		if (sources[i].key === sourceKey) {
			value = sources[i].source;
			found = true;
			break;
		}
	}

	if (!found) {
		return undefined;
	}

	// Traverse the path; modifier-shaped parts apply as transforms.
	for (const part of parts.slice(1)) {
		if (isModifier(part)) {
			value = applyModifier(value, part);
			continue;
		}

		if (
			value === null ||
			value === undefined ||
			typeof value !== 'object'
		) {
			return undefined;
		}

		const obj = value as Record<string, unknown>;
		if (!(part in obj)) {
			return undefined;
		}

		value = obj[part];
	}

	return value;
}

/**
 * Split an expression into parts on dots, respecting parenthesis depth.
 *
 * For example:
 *   "this.title"                  → ['this', 'title']
 *   "this.title.truncateWords(3)" → ['this', 'title', 'truncateWords(3)']
 */
function splitExpression(expression: string): string[] {
	const parts: string[] = [];
	let current = '';
	let parenDepth = 0;

	for (const char of expression) {
		if (char === '(') {
			parenDepth++;
			current += char;
		} else if (char === ')') {
			parenDepth = Math.max(0, parenDepth - 1);
			current += char;
		} else if (char === '.' && parenDepth === 0) {
			if (current !== '') {
				parts.push(current);
			}
			current = '';
		} else {
			current += char;
		}
	}

	if (current !== '') {
		parts.push(current);
	}

	return parts;
}
