/**
 * Modifiers — transform values in dynamic expressions.
 *
 * Each modifier is a function that receives the current value as its first
 * argument and optional extra arguments from the expression syntax.
 */

type ModifierFn = (value: unknown, ...args: unknown[]) => unknown;

/**
 * Check whether a string looks like a modifier call (e.g. "truncateWords(3)").
 */
export function isModifier(part: string): boolean {
	return /^\w+\(.*\)$/s.test(part);
}

/**
 * Parse a modifier string into its method name and typed arguments.
 */
export function parseModifier(
	modifierString: string,
): { method: string; args: unknown[] } | undefined {
	if (!isModifier(modifierString)) {
		return undefined;
	}

	const parenPos = modifierString.indexOf('(');
	const method = modifierString.slice(0, parenPos);
	const argString = modifierString.slice(parenPos + 1, -1).trim();

	if (argString === '') {
		return { method, args: [] };
	}

	const rawArgs = splitArgs(argString);
	const args = rawArgs.map(castArg);

	return { method, args };
}

/**
 * Apply a modifier from its raw string form to a value.
 */
export function applyModifier(
	value: unknown,
	modifierString: string,
): unknown {
	const parsed = parseModifier(modifierString);
	if (!parsed) {
		return value;
	}

	const fn = getModifier(parsed.method);
	if (!fn) {
		return value;
	}

	return fn(value, ...parsed.args);
}

/**
 * Get the function for a named modifier.
 */
export function getModifier(method: string): ModifierFn | undefined {
	switch (method) {
		case 'truncateWords':
			return (value: unknown, ...args: unknown[]) => {
				if (typeof value !== 'string') {
					return value;
				}

				let wordCount = 0;
				if (typeof args[0] === 'number') {
					wordCount = args[0];
				}

				const ellipsis =
					typeof args[1] === 'string' ? args[1] : '...';

				const words = value.split(' ');
				if (words.length <= wordCount) {
					return value;
				}

				return words.slice(0, wordCount).join(' ') + ellipsis;
			};

		case 'toUpperCase':
			return (value: unknown) => {
				if (typeof value !== 'string') {
					return value;
				}
				return value.toUpperCase();
			};

		case 'toLowerCase':
			return (value: unknown) => {
				if (typeof value !== 'string') {
					return value;
				}
				return value.toLowerCase();
			};

		case 'truncateChars':
			return (value: unknown, ...args: unknown[]) => {
				if (typeof value !== 'string') {
					return value;
				}

				let charCount = 0;
				if (typeof args[0] === 'number') {
					charCount = args[0];
				}

				const ellipsis =
					typeof args[1] === 'string' ? args[1] : '...';

				if (value.length <= charCount) {
					return value;
				}

				return value.slice(0, charCount) + ellipsis;
			};

		default:
			return undefined;
	}
}

/**
 * Split a comma-separated argument string, respecting quotes and nesting.
 */
function splitArgs(argString: string): string[] {
	const args: string[] = [];
	let current = '';
	let depth = 0;
	let inString = false;
	let stringChar = '';

	for (let i = 0; i < argString.length; i++) {
		const char = argString[i];
		const prevChar = i > 0 ? argString[i - 1] : '';

		if (
			(char === '"' || char === "'") &&
			prevChar !== '\\'
		) {
			if (!inString) {
				inString = true;
				stringChar = char;
			} else if (char === stringChar) {
				inString = false;
				stringChar = '';
			}
		}

		if (!inString) {
			if (char === '(' || char === '[' || char === '{') {
				depth++;
			} else if (char === ')' || char === ']' || char === '}') {
				depth--;
			}

			if (char === ',' && depth === 0) {
				args.push(current.trim());
				current = '';
				continue;
			}
		}

		current += char;
	}

	const trimmed = current.trim();
	if (trimmed !== '') {
		args.push(trimmed);
	}

	return args;
}

/**
 * Cast a raw argument string to its appropriate JS type.
 */
function castArg(arg: string): unknown {
	// Quoted string — strip quotes.
	const stringMatch = arg.match(/^(['"])(.*)\1$/s);
	if (stringMatch) {
		return stringMatch[2];
	}

	// Boolean.
	if (arg === 'true') return true;
	if (arg === 'false') return false;

	// Null / undefined.
	if (arg === 'null') return null;
	if (arg === 'undefined') return undefined;

	// Numeric.
	const num = Number(arg);
	if (!isNaN(num) && arg.trim() !== '') {
		return num;
	}

	return arg;
}
