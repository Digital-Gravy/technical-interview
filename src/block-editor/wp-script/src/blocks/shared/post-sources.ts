import { useSelect } from '@wordpress/data';
import type { Source } from '@utilities/DynamicContentProcessor';

interface EditorStore {
	getCurrentPostId: () => number | undefined;
	getCurrentPostAttribute: (attribute: string) => unknown;
}

/**
 * Build builder-style expression sources from the current Gutenberg post.
 */
export function usePostSources(): Source[] {
	return useSelect((select) => {
		const editor = select('core/editor') as EditorStore;

		const id = editor.getCurrentPostId?.();
		const title = editor.getCurrentPostAttribute?.('title');
		const slug = editor.getCurrentPostAttribute?.('slug');
		const excerpt = editor.getCurrentPostAttribute?.('excerpt');
		const date = editor.getCurrentPostAttribute?.('date');
		const type = editor.getCurrentPostAttribute?.('type');

		return [
			{
				key: 'this',
				source: {
					id: typeof id === 'number' ? id : 0,
					title: typeof title === 'string' ? title : '',
					slug: typeof slug === 'string' ? slug : '',
					excerpt: typeof excerpt === 'string' ? excerpt : '',
					date: typeof date === 'string' ? date : '',
					type: typeof type === 'string' ? type : '',
				},
			},
		];
	}, []);
}
