import { describe, it, expect } from 'vitest';
import { render } from '@testing-library/svelte';
import DivBlock from './DivBlock.svelte';

describe('DivBlock', () => {
	it('renders empty div when no children provided', () => {
		const { container } = render(DivBlock);

		expect(container.querySelector('div')).not.toBeNull();
		expect(container.querySelector('div')?.textContent).toBe('');
	});

	it('renders with no class attribute when className is empty', () => {
		const { container } = render(DivBlock);

		expect(container.querySelector('div')?.getAttribute('class')).toBeNull();
	});

	it('renders class attribute when className is provided', () => {
		const { container } = render(DivBlock, {
			props: { className: 'my-class' },
		});

		expect(container.querySelector('div.my-class')).not.toBeNull();
	});

	it('renders id attribute when attributes has id', () => {
		const { container } = render(DivBlock, {
			props: { htmlAttributes: { id: 'wrapper' } },
		});

		expect(container.querySelector('div')?.getAttribute('id')).toBe(
			'wrapper',
		);
	});

	it('renders data attribute when attributes has data-test', () => {
		const { container } = render(DivBlock, {
			props: { htmlAttributes: { 'data-test': 'value' } },
		});

		expect(container.querySelector('div')?.getAttribute('data-test')).toBe(
			'value',
		);
	});

	it('renders no extra attributes when attributes is empty', () => {
		const { container } = render(DivBlock);

		const div = container.querySelector('div')!;
		expect(div.attributes.length).toBe(0);
	});
});
