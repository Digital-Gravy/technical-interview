/**
 * Builder API client — handles all REST communication with WordPress.
 */

import type { Source } from '@utilities/DynamicContentProcessor';

export interface BuilderConfig {
	postId: number;
	restUrl: string;
	saveUrl: string;
	nonce: string;
	sources: Source[];
}

/**
 * Read the config object injected by PHP into the page.
 */
export function getConfig(): BuilderConfig {
	return (window as unknown as { dgBuilderConfig: BuilderConfig })
		.dgBuilderConfig;
}

/**
 * Fetch a post's rendered content from the WP REST API.
 */
export async function fetchPostContent(config: BuilderConfig): Promise<string> {
	const separator = config.restUrl.includes('?') ? '&' : '?';
	const url = `${config.restUrl}${config.postId}${separator}context=edit`;

	const res = await fetch(url, {
		credentials: 'same-origin',
		headers: { 'X-WP-Nonce': config.nonce },
	});

	if (!res.ok) throw new Error(`Failed to load post (${res.status})`);

	const post = await res.json();
	return post.content?.rendered ?? '';
}

/**
 * Save HTML content back to the post via the builder save route.
 */
export async function savePostContent(
	config: BuilderConfig,
	html: string,
): Promise<void> {
	const url = `${config.saveUrl}${config.postId}/content`;

	const res = await fetch(url, {
		method: 'POST',
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': config.nonce,
		},
		body: JSON.stringify({ html }),
	});

	if (!res.ok) throw new Error(`Save failed (${res.status})`);
}
