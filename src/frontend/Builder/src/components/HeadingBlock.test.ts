import { describe, it, expect } from 'vitest';
import { render } from '@testing-library/svelte';
import HeadingBlock from './HeadingBlock.svelte';

describe('HeadingBlock', () => {
	it('renders empty heading when no content provided', () => {
		const { container } = render(HeadingBlock);

		expect(container.querySelector('h2')).not.toBeNull();
		expect(container.querySelector('h2')?.textContent).toBe('');
	});

	it('renders h2 by default when no level specified', () => {
		const { container } = render(HeadingBlock, { props: { content: 'Test' } });

		expect(container.querySelector('h2')).not.toBeNull();
		expect(container.querySelector('h2')?.textContent).toBe('Test');
	});

	it('renders correct tag when level is 1', () => {
		const { container } = render(HeadingBlock, {
			props: { level: 1, content: 'Title' },
		});

		expect(container.querySelector('h1')).not.toBeNull();
		expect(container.querySelector('h1')?.textContent).toBe('Title');
	});

	it('renders h4 when level is 4', () => {
		const { container } = render(HeadingBlock, {
			props: { level: 4, content: 'Subheading' },
		});

		expect(container.querySelector('h4')).not.toBeNull();
		expect(container.querySelector('h4')?.textContent).toBe('Subheading');
	});

	it('renders h6 when level is 6', () => {
		const { container } = render(HeadingBlock, {
			props: { level: 6, content: 'Small' },
		});

		expect(container.querySelector('h6')).not.toBeNull();
		expect(container.querySelector('h6')?.textContent).toBe('Small');
	});

	it('falls back to h2 when level is 0', () => {
		const { container } = render(HeadingBlock, {
			props: { level: 0, content: 'Zero' },
		});

		expect(container.querySelector('h2')?.textContent).toBe('Zero');
	});

	it('falls back to h2 when level is negative', () => {
		const { container } = render(HeadingBlock, {
			props: { level: -1, content: 'Negative' },
		});

		expect(container.querySelector('h2')?.textContent).toBe('Negative');
	});

	it('falls back to h2 when level is out of range', () => {
		const { container } = render(HeadingBlock, {
			props: { level: 9, content: 'Fallback' },
		});

		expect(container.querySelector('h9')).toBeNull();
		expect(container.querySelector('h2')?.textContent).toBe('Fallback');
	});

	it('renders id attribute when attributes has id', () => {
		const { container } = render(HeadingBlock, {
			props: { level: 1, content: 'Title', htmlAttributes: { id: 'main' } },
		});

		expect(container.querySelector('h1')?.getAttribute('id')).toBe('main');
	});

	it('renders no extra attributes when attributes is empty', () => {
		const { container } = render(HeadingBlock, {
			props: { content: 'Title', htmlAttributes: {} },
		});

		const h2 = container.querySelector('h2')!;
		expect(h2.attributes.length).toBe(0);
	});
});
