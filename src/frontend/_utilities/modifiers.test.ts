import { describe, it, expect } from 'vitest';
import {
	getModifier,
	isModifier,
	parseModifier,
	applyModifier,
} from './modifiers';

describe('Modifiers', () => {
	// ── truncateWords ────────────────────────────────────────────────

	it('truncates to word count when input has simple spaces', () => {
		const modifier = getModifier('truncateWords');
		expect(modifier).toBeDefined();
		expect(modifier!('The quick brown fox jumps', 3)).toBe(
			'The quick brown...',
		);
	});

	it('returns original when word count not exceeded', () => {
		const modifier = getModifier('truncateWords');
		expect(modifier!('Hello world', 5)).toBe('Hello world');
	});

	it('returns original when value is not a string', () => {
		const modifier = getModifier('truncateWords');
		expect(modifier!(42, 3)).toBe(42);
	});

	it('uses custom ellipsis when provided', () => {
		const modifier = getModifier('truncateWords');
		expect(modifier!('The quick brown fox', 2, '—')).toBe('The quick—');
	});

	// ── toUpperCase ──────────────────────────────────────────────────

	it('converts to uppercase when applied', () => {
		const modifier = getModifier('toUpperCase');
		expect(modifier).toBeDefined();
		expect(modifier!('hello world')).toBe('HELLO WORLD');
	});

	it('returns original when value is not a string for toUpperCase', () => {
		const modifier = getModifier('toUpperCase');
		expect(modifier!(42)).toBe(42);
	});

	// ── toLowerCase ──────────────────────────────────────────────────

	it('converts to lowercase when applied', () => {
		const modifier = getModifier('toLowerCase');
		expect(modifier).toBeDefined();
		expect(modifier!('HELLO WORLD')).toBe('hello world');
	});

	// ── truncateChars ────────────────────────────────────────────────

	it('truncates to character count when input exceeds limit', () => {
		const modifier = getModifier('truncateChars');
		expect(modifier).toBeDefined();
		expect(modifier!('Hello World', 3)).toBe('Hel...');
	});

	it('returns original when character count not exceeded', () => {
		const modifier = getModifier('truncateChars');
		expect(modifier!('Hi', 10)).toBe('Hi');
	});

	// ── Unknown modifier ─────────────────────────────────────────────

	it('returns undefined when modifier not found', () => {
		expect(getModifier('nonExistent')).toBeUndefined();
	});

	// ── isModifier ───────────────────────────────────────────────────

	it('detects modifier when part has parentheses', () => {
		expect(isModifier('truncateWords(3)')).toBe(true);
		expect(isModifier('toUpperCase()')).toBe(true);
		expect(isModifier('default("fallback")')).toBe(true);
	});

	it('rejects non-modifier when part has no parentheses', () => {
		expect(isModifier('title')).toBe(false);
		expect(isModifier('user')).toBe(false);
	});

	// ── parseModifier ────────────────────────────────────────────────

	it('parses method and args when modifier has arguments', () => {
		const result = parseModifier('truncateWords(3)');
		expect(result).toEqual({ method: 'truncateWords', args: [3] });
	});

	it('parses empty args when modifier has no arguments', () => {
		const result = parseModifier('toUpperCase()');
		expect(result).toEqual({ method: 'toUpperCase', args: [] });
	});

	it('parses quoted string args when modifier has string arguments', () => {
		const result = parseModifier('truncateWords(3, "—")');
		expect(result).toEqual({ method: 'truncateWords', args: [3, '—'] });
	});

	it('returns undefined when input is not a modifier', () => {
		expect(parseModifier('title')).toBeUndefined();
	});

	// ── applyModifier ────────────────────────────────────────────────

	it('applies modifier from raw string when valid', () => {
		expect(applyModifier('hello world', 'toUpperCase()')).toBe(
			'HELLO WORLD',
		);
	});

	it('returns original value when modifier string is unknown', () => {
		expect(applyModifier('hello', 'nonExistent()')).toBe('hello');
	});

	it('applies modifier with args from raw string when valid', () => {
		expect(applyModifier('The quick brown fox', 'truncateWords(2)')).toBe(
			'The quick...',
		);
	});
});
