import { PanelBody, TextControl } from '@wordpress/components';

export interface HtmlAttributeField {
	key: string;
	label: string;
}

interface HtmlAttributesPanelProps {
	htmlAttributes: Record<string, string>;
	onChange: (nextAttributes: Record<string, string>) => void;
	fields: HtmlAttributeField[];
	title?: string;
}

export function HtmlAttributesPanel({
	htmlAttributes,
	onChange,
	fields,
	title = 'HTML Attributes',
}: HtmlAttributesPanelProps) {
	const setAttribute = (key: string, value: string) => {
		const next = { ...htmlAttributes };
		if (value) {
			next[key] = value;
		} else {
			delete next[key];
		}
		onChange(next);
	};

	return (
		<PanelBody title={title}>
			{fields.map((field) => (
				<TextControl
					key={field.key}
					label={field.label}
					value={htmlAttributes[field.key] ?? ''}
					onChange={(value: string) => setAttribute(field.key, value)}
				/>
			))}
		</PanelBody>
	);
}
