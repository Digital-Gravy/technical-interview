import { registerBlockType } from '@wordpress/blocks';
import { createElement } from 'react';
import {
	useBlockProps,
	RichText,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { BLOCK_PREFIX } from '../../block-names';
import { replaceTemplates } from '@utilities/DynamicContentProcessor';
import {
	HtmlAttributesPanel,
	type HtmlAttributeField,
} from '../shared/html-attributes-panel';
import { usePostSources } from '../shared/post-sources';

interface HeadingBlockAttributes {
	level: number;
	content: string;
	htmlAttributes: Record<string, string>;
}

interface EditProps {
	attributes: HeadingBlockAttributes;
	setAttributes: (attrs: Partial<HeadingBlockAttributes>) => void;
	isSelected: boolean;
}

const LEVEL_OPTIONS = [
	{ label: 'H1', value: '1' },
	{ label: 'H2', value: '2' },
	{ label: 'H3', value: '3' },
	{ label: 'H4', value: '4' },
	{ label: 'H5', value: '5' },
	{ label: 'H6', value: '6' },
];

const HTML_ATTRIBUTE_FIELDS: HtmlAttributeField[] = [
	{ key: 'id', label: 'ID' },
	{ key: 'class', label: 'Class' },
	{ key: 'style', label: 'Style' },
];

const Edit = ({ attributes, setAttributes, isSelected }: EditProps) => {
	const { level, content, htmlAttributes } = attributes;
	const blockProps = useBlockProps();
	const sources = usePostSources();
	const previewContent = replaceTemplates(content, sources);
	const tagName = `h${level}` as keyof HTMLElementTagNameMap;

	return (
		<>
			<InspectorControls>
				<PanelBody title="Settings">
					<SelectControl
						label="Heading Level"
						value={String(level)}
						options={LEVEL_OPTIONS}
						onChange={(value: string) =>
							setAttributes({ level: Number(value) })
						}
						/>
					</PanelBody>
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
						tagName={tagName}
						value={content}
						onChange={(value: string) => setAttributes({ content: value })}
						placeholder="Write heading…"
					/>
				) : (
					createElement(tagName, {
						...blockProps,
						dangerouslySetInnerHTML: { __html: previewContent },
					})
				)}
			</>
		);
};

const Save = () => {
	return null;
};

export default function registerHeadingBlock() {
	registerBlockType(`${BLOCK_PREFIX}/heading`, {
		apiVersion: 3,
		title: 'DG Heading',
		category: 'dg-interview',
		icon: 'heading',
		description: 'A heading block with configurable level (h1–h6).',
		attributes: {
			level: {
				type: 'number' as const,
				default: 2,
			},
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
