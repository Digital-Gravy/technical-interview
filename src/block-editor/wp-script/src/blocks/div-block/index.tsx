import { registerBlockType } from '@wordpress/blocks';
import {
	useBlockProps,
	useInnerBlocksProps,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { InnerBlocks } from '@wordpress/block-editor';
import { BLOCK_PREFIX } from '../../block-names';
import {
	HtmlAttributesPanel,
	type HtmlAttributeField,
} from '../shared/html-attributes-panel';

interface DivBlockAttributes {
	className: string;
	htmlAttributes: Record<string, string>;
}

interface EditProps {
	attributes: DivBlockAttributes;
	setAttributes: (attrs: Partial<DivBlockAttributes>) => void;
}

const HTML_ATTRIBUTE_FIELDS: HtmlAttributeField[] = [
	{ key: 'id', label: 'ID' },
	{ key: 'style', label: 'Style' },
];

const Edit = ({ attributes, setAttributes }: EditProps) => {
	const { className, htmlAttributes } = attributes;
	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps(blockProps);

	return (
		<>
			<InspectorControls>
				<PanelBody title="Settings">
					<TextControl
						label="CSS Class"
						value={className}
						onChange={(value: string) =>
							setAttributes({ className: value })
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
				<div {...innerBlocksProps} />
		</>
	);
};

const Save = () => {
	return <InnerBlocks.Content />;
};

export default function registerDivBlock() {
	registerBlockType(`${BLOCK_PREFIX}/div`, {
		apiVersion: 3,
		title: 'DG Div',
		category: 'dg-interview',
		icon: 'screenoptions',
		description: 'A simple div wrapper.',
		attributes: {
			className: {
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
