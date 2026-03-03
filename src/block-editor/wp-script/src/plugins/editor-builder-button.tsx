import { registerPlugin } from '@wordpress/plugins';
import { PluginMoreMenuItem } from '@wordpress/edit-post';
import { useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { external } from '@wordpress/icons';

const HEADER_SETTINGS_SELECTOR = '.editor-header__settings';
const PREVIEW_BUTTON_SELECTOR = '.editor-post-preview';
const HEADER_BUTTON_CLASS = 'dg-interview-builder-header-button';

const BuilderHeaderButton = ({ href }: { href: string }) => {
	useEffect(() => {
		const ensureButton = () => {
			const settingsRow = document.querySelector(HEADER_SETTINGS_SELECTOR);
			if (!settingsRow) {
				return;
			}

			let button = settingsRow.querySelector(
				`.${HEADER_BUTTON_CLASS}`,
			) as HTMLAnchorElement | null;

			if (!button) {
				button = document.createElement('a');
				button.className = `components-button is-secondary ${HEADER_BUTTON_CLASS}`;
				button.textContent = 'Edit in Builder';
				button.style.marginRight = '8px';

				const previewButton = settingsRow.querySelector(PREVIEW_BUTTON_SELECTOR);
				if (previewButton) {
					settingsRow.insertBefore(button, previewButton.nextSibling);
				} else {
					settingsRow.prepend(button);
				}
			}

			button.href = href;
		};

		ensureButton();

		const observer = new MutationObserver(() => {
			ensureButton();
		});
		observer.observe(document.body, {
			childList: true,
			subtree: true,
		});

		return () => {
			observer.disconnect();
			document
				.querySelectorAll(`.${HEADER_BUTTON_CLASS}`)
				.forEach((button) => button.remove());
		};
	}, [href]);

	return null;
};

const BuilderMenuItem = () => {
	const postId = useSelect(
		(select) =>
			(select('core/editor') as { getCurrentPostId: () => number })
				.getCurrentPostId(),
		[]
	);

	const builderUrl = `/?dg-builder=1&post_id=${postId}`;

	return (
		<>
			<PluginMoreMenuItem icon={external} href={builderUrl}>
				Edit in Builder
			</PluginMoreMenuItem>
			<BuilderHeaderButton href={builderUrl} />
		</>
	);
};

export default function registerEditorBuilderButton() {
	registerPlugin('dg-interview-builder-button', {
		render: BuilderMenuItem,
	});
}
