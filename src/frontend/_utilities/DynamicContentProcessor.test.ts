import { describe, it, expect } from 'vitest';
import {
	replaceTemplates,
	resolveExpression,
	type Source,
} from './DynamicContentProcessor';

describe('DynamicContentProcessor', () => {
	// ── replaceTemplates ──────────────────────────────────────────────

	it('replaces expression when source matches', () => {
		const sources: Source[] = [
			{ key: 'this', source: { name: 'World' } },
		];

		expect(replaceTemplates('Hello {this.name}!', sources)).toBe(
			'Hello World!',
		);
	});

	it('returns original when no expressions', () => {
		expect(replaceTemplates('Just plain text', [])).toBe('Just plain text');
	});

	it('replaces multiple expressions when string has several', () => {
		const sources: Source[] = [
			{ key: 'this', source: { first: 'Hello', last: 'World' } },
		];

		expect(replaceTemplates('{this.first} {this.last}', sources)).toBe(
			'Hello World',
		);
	});

	it('returns empty for expression when source not found', () => {
		const sources: Source[] = [
			{ key: 'this', source: { name: 'World' } },
		];

		expect(replaceTemplates('Hello {this.missing}!', sources)).toBe(
			'Hello !',
		);
	});

	it('resolves nested property when dot path used', () => {
		const sources: Source[] = [
			{ key: 'this', source: { user: { name: 'Alice' } } },
		];

		expect(replaceTemplates('Hi {this.user.name}', sources)).toBe(
			'Hi Alice',
		);
	});

	// ── resolveExpression ─────────────────────────────────────────────

	it('resolves simple property when source matches', () => {
		const sources: Source[] = [
			{ key: 'this', source: { title: 'My Post' } },
		];

		expect(resolveExpression('this.title', sources)).toBe('My Post');
	});

	it('returns undefined when source key not found', () => {
		const sources: Source[] = [
			{ key: 'this', source: { title: 'My Post' } },
		];

		expect(resolveExpression('other.title', sources)).toBeUndefined();
	});

	it('returns undefined when property not found', () => {
		const sources: Source[] = [
			{ key: 'this', source: { title: 'My Post' } },
		];

		expect(resolveExpression('this.missing', sources)).toBeUndefined();
	});

	it('uses last source when keys conflict', () => {
		const sources: Source[] = [
			{ key: 'this', source: { title: 'First' } },
			{ key: 'this', source: { title: 'Last' } },
		];

		expect(resolveExpression('this.title', sources)).toBe('Last');
	});

	it('resolves deep path when multiple levels', () => {
		const sources: Source[] = [
			{ key: 'this', source: { a: { b: { c: 'deep' } } } },
		];

		expect(resolveExpression('this.a.b.c', sources)).toBe('deep');
	});

	// ── Modifier integration ─────────────────────────────────────────

	it('applies modifier when expression has dot modifier', () => {
		const sources: Source[] = [
			{ key: 'this', source: { title: 'hello world' } },
		];

		expect(
			replaceTemplates('{this.title.toUpperCase()}', sources),
		).toBe('HELLO WORLD');
	});

	it('chains modifiers when multiple dot modifiers used', () => {
		const sources: Source[] = [
			{
				key: 'this',
				source: { title: 'the quick brown fox jumps' },
			},
		];

		expect(
			replaceTemplates(
				'{this.title.truncateWords(3).toUpperCase()}',
				sources,
			),
		).toBe('THE QUICK BROWN...');
	});

	it('resolves value without modifiers when no modifier in path', () => {
		const sources: Source[] = [
			{ key: 'this', source: { title: 'Hello' } },
		];

		expect(replaceTemplates('{this.title}', sources)).toBe('Hello');
	});

	it('returns empty when modifier applied to missing source', () => {
		const sources: Source[] = [
			{ key: 'this', source: { title: 'Hello' } },
		];

		expect(
			replaceTemplates('{this.missing.toUpperCase()}', sources),
		).toBe('');
	});
});
