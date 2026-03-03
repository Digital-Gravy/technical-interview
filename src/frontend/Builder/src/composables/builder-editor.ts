import { EditorView, basicSetup } from 'codemirror';
import { html } from '@codemirror/lang-html';
import { EditorState, RangeSetBuilder } from '@codemirror/state';
import { keymap, Decoration, ViewPlugin } from '@codemirror/view';

const dataAttrPattern = / data-dg-[\w-]+="[^"]*"/g;

function buildDataAttrDecos(view: EditorView) {
	const builder = new RangeSetBuilder<Decoration>();
	for (const { from, to } of view.visibleRanges) {
		const text = view.state.doc.sliceString(from, to);
		let match: RegExpExecArray | null;
		dataAttrPattern.lastIndex = 0;
		while ((match = dataAttrPattern.exec(text))) {
			const start = from + match.index;
			const end = start + match[0].length;
			builder.add(
				start,
				end,
				Decoration.mark({
					class: 'cm-dg-data-attr',
					attributes: { title: match[0].trim() },
				}),
			);
		}
	}
	return builder.finish();
}

const dataAttrPlugin = ViewPlugin.define(
	(view) => ({
		decorations: buildDataAttrDecos(view),
		update(update) {
			if (update.docChanged || update.viewportChanged) {
				this.decorations = buildDataAttrDecos(update.view);
			}
		},
	}),
	{ decorations: (value) => value.decorations },
);

export interface BuilderEditorController {
	getContent: () => string;
	destroy: () => void;
}

interface CreateBuilderEditorArgs {
	container: HTMLElement;
	initialContent: string;
	onDocChanged: (content: string) => void;
	onSaveShortcut: () => void;
}

export function createBuilderEditor({
	container,
	initialContent,
	onDocChanged,
	onSaveShortcut,
}: CreateBuilderEditorArgs): BuilderEditorController {
	const state = EditorState.create({
		doc: initialContent,
		extensions: [
			keymap.of([
				{
					key: 'Mod-s',
					run: () => {
						onSaveShortcut();
						return true;
					},
				},
			]),
			basicSetup,
			html(),
			dataAttrPlugin,
			EditorView.updateListener.of((update) => {
				if (update.docChanged) {
					onDocChanged(update.state.doc.toString());
				}
			}),
		],
	});

	const view = new EditorView({
		state,
		parent: container,
	});

	return {
		getContent: () => view.state.doc.toString(),
		destroy: () => view.destroy(),
	};
}
