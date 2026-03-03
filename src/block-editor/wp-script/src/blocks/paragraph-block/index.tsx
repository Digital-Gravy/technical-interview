import { registerBlockType } from '@wordpress/blocks';
import {
	useBlockProps,
	RichText,
	InspectorControls,
} from '@wordpress/block-editor';
import { BLOCK_PREFIX } from '../../block-names';
import { replaceTemplates } from '@utilities/DynamicContentProcessor';
import {
	HtmlAttributesPanel,
	type HtmlAttributeField,
} from '../shared/html-attributes-panel';
import { usePostSources } from '../shared/post-sources';

interface ParagraphBlockAttributes {
	content: string;
	htmlAttributes: Record<string, string>;
}

interface EditProps {
	attributes: ParagraphBlockAttributes;
	setAttributes: (attrs: Partial<ParagraphBlockAttributes>) => void;
	isSelected: boolean;
}

const HTML_ATTRIBUTE_FIELDS: HtmlAttributeField[] = [
	{ key: 'id', label: 'ID' },
	{ key: 'class', label: 'Class' },
	{ key: 'style', label: 'Style' },
];

const Edit = ({ attributes, setAttributes, isSelected }: EditProps) => {
	const { content, htmlAttributes } = attributes;
	const blockProps = useBlockProps();
	const sources = usePostSources();
	const previewContent = replaceTemplates(content, sources);

	return (
		<>
			<InspectorControls>
				<HtmlAttributesPanel
					htmlAttributes={htmlAttributes}
					fields={HTML_ATTRIBUTE_FIELDS}
					onChange={(nextAttributes) =>
						setAttributes({ htmlAttributes: nextAttributes })
					}
				/>
			</InspectorControls>
				{isSelected ? (
					<RichText
						{...blockProps}
						tagName="p"
						value={content}
						onChange={(value: string) => setAttributes({ content: value })}
						placeholder="Write paragraph…"
					/>
				) : (
					<p
						{...blockProps}
						dangerouslySetInnerHTML={{ __html: previewContent }}
					/>
				)}
			</>
		);
};

const Save = () => {
	return null;
};

export default function registerParagraphBlock() {
	registerBlockType(`${BLOCK_PREFIX}/paragraph`, {
		apiVersion: 3,
		title: 'DG Paragraph',
		category: 'dg-interview',
		icon: 'editor-paragraph',
		description: 'A paragraph block.',
		attributes: {
			content: {
				type: 'string' as const,
				default: '',
			},
			htmlAttributes: {
				type: 'object' as const,
				default: {},
			},
		},
		supports: {
			html: false,
		},
		edit: Edit,
		save: Save,
	});
}
