import { replaceTemplates } from '@utilities/DynamicContentProcessor';
import type { Source } from '@utilities/DynamicContentProcessor';

export function renderPreview(
	iframe: HTMLIFrameElement | undefined,
	content: string,
	sources: Source[],
): void {
	const doc = iframe?.contentDocument;
	if (!doc) return;

	const resolved = replaceTemplates(content, sources);
	doc.open();
	doc.write(resolved);
	doc.close();
}
