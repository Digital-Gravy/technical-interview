import { describe, it, expect } from 'vitest';
import { render } from '@testing-library/svelte';
import ParagraphBlock from './ParagraphBlock.svelte';

describe('ParagraphBlock', () => {
	it('renders content when content is provided', () => {
		const { container } = render(ParagraphBlock, {
			props: { content: 'Hello world' },
		});

		expect(container.querySelector('p')).not.toBeNull();
		expect(container.querySelector('p')?.textContent).toBe('Hello world');
	});

	it('renders empty paragraph when no content provided', () => {
		const { container } = render(ParagraphBlock);

		expect(container.querySelector('p')).not.toBeNull();
		expect(container.querySelector('p')?.textContent).toBe('');
	});

	it('renders id attribute when attributes has id', () => {
		const { container } = render(ParagraphBlock, {
			props: { content: 'Hello', htmlAttributes: { id: 'intro' } },
		});

		expect(container.querySelector('p')?.getAttribute('id')).toBe('intro');
	});

	it('renders data attribute when attributes has data-test', () => {
		const { container } = render(ParagraphBlock, {
			props: { content: 'Hello', htmlAttributes: { 'data-test': 'value' } },
		});

		expect(container.querySelector('p')?.getAttribute('data-test')).toBe(
			'value',
		);
	});

	it('renders no extra attributes when attributes is empty', () => {
		const { container } = render(ParagraphBlock, {
			props: { content: 'Hello', htmlAttributes: {} },
		});

		const p = container.querySelector('p')!;
		expect(p.attributes.length).toBe(0);
	});
});
