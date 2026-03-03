<script lang="ts">
	import { onDestroy, onMount, tick } from 'svelte';
	import { getConfig, fetchPostContent, savePostContent } from './api';
	import {
		createBuilderEditor,
		type BuilderEditorController,
	} from './composables/builder-editor';
	import { renderPreview } from './composables/preview';

	const config = getConfig();

	let editorContainer: HTMLDivElement;
	let previewIframe: HTMLIFrameElement;
	let editorController: BuilderEditorController | undefined;
	let loading = $state(true);
	let saving = $state(false);
	let saveStatus = $state<'idle' | 'saved' | 'error'>('idle');
	let error = $state('');

	function updatePreview(content: string): void {
		renderPreview(previewIframe, content, config.sources ?? []);
	}

	function initEditor(content: string) {
		editorController?.destroy();
		editorController = createBuilderEditor({
			container: editorContainer,
			initialContent: content,
			onDocChanged: updatePreview,
			onSaveShortcut: () => {
				void save();
			},
		});

		requestAnimationFrame(() => updatePreview(content));
	}

	async function save() {
		if (!editorController || saving) return;
		saving = true;
		saveStatus = 'idle';

		try {
			await savePostContent(config, editorController.getContent());
			saveStatus = 'saved';
			setTimeout(() => (saveStatus = 'idle'), 2000);
		} catch (err: unknown) {
			saveStatus = 'error';
			console.error('[Builder] save error', err);
		} finally {
			saving = false;
		}
	}

	$effect(() => {
		if (!config?.postId) {
			error = 'No post ID provided.';
			loading = false;
			return;
		}

		fetchPostContent(config)
			.then(async (content) => {
				loading = false;
				await tick();
				initEditor(content);
			})
			.catch((err) => {
				error = err.message;
				loading = false;
			});
	});

	onMount(() => {
		const onKeyDown = (event: KeyboardEvent) => {
			if (event.defaultPrevented) return;
			const isSave =
				(event.metaKey || event.ctrlKey) &&
				event.key.toLowerCase() === 's';
			if (!isSave) return;

			event.preventDefault();
			void save();
		};

		window.addEventListener('keydown', onKeyDown);
		return () => {
			window.removeEventListener('keydown', onKeyDown);
		};
	});

	onDestroy(() => {
		editorController?.destroy();
		editorController = undefined;
	});
</script>

{#if error}
	<div class="builder__message builder__message--error">{error}</div>
{:else if loading}
	<div class="builder__message">Loading…</div>
{:else}
	<div class="builder">
		<div class="builder__toolbar">
			<span class="builder__toolbar-title">Builder</span>
			<div class="builder__toolbar-actions">
				{#if saveStatus === 'saved'}
					<span class="builder__save-status">Saved</span>
				{:else if saveStatus === 'error'}
					<span class="builder__save-status builder__save-status--error">Save failed</span>
				{/if}
				<button class="builder__save-btn" onclick={save} disabled={saving}>
					{saving ? 'Saving…' : 'Save'}
				</button>
			</div>
		</div>
		<div class="builder__panels">
			<div class="builder__editor">
				<div class="builder__editor-header">HTML</div>
				<div class="builder__editor-content" bind:this={editorContainer}></div>
			</div>
			<div class="builder__preview">
				<div class="builder__preview-header">Preview</div>
				<iframe
					class="builder__preview-iframe"
					title="Preview"
					bind:this={previewIframe}
				></iframe>
			</div>
		</div>
	</div>
{/if}

<style>
	:global(html),
	:global(body) {
		margin: 0;
		height: 100%;
		overflow: hidden;
		font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
	}

	.builder {
		display: flex;
		flex-direction: column;
		height: 100vh;
	}

	.builder__toolbar {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 8px 16px;
		background: #1e1e1e;
		color: #fff;
	}

	.builder__toolbar-title {
		font-size: 13px;
		font-weight: 600;
	}

	.builder__toolbar-actions {
		display: flex;
		align-items: center;
		gap: 12px;
	}

	.builder__save-status {
		font-size: 12px;
		color: #68de7c;
	}

	.builder__save-status--error {
		color: #f87171;
	}

	.builder__save-btn {
		padding: 6px 16px;
		font-size: 13px;
		font-weight: 500;
		color: #1e1e1e;
		background: #fff;
		border: none;
		border-radius: 3px;
		cursor: pointer;
	}

	.builder__save-btn:disabled {
		opacity: 0.6;
		cursor: default;
	}

	.builder__panels {
		display: flex;
		flex: 1;
		min-height: 0;
	}

	.builder__editor {
		flex: 1;
		display: flex;
		flex-direction: column;
		min-width: 0;
		border-right: 1px solid #ddd;
	}

	.builder__editor-header,
	.builder__preview-header {
		padding: 8px 12px;
		font-size: 12px;
		font-weight: 600;
		text-transform: uppercase;
		letter-spacing: 0.05em;
		color: #666;
		background: #f5f5f5;
		border-bottom: 1px solid #ddd;
	}

	.builder__editor-content {
		flex: 1;
		overflow: auto;
	}

	.builder__editor-content :global(.cm-editor) {
		height: 100%;
	}

	.builder__editor-content :global(.cm-dg-data-attr) {
		font-size: 0;
		position: relative;
	}

	.builder__editor-content :global(.cm-dg-data-attr)::before {
		content: ' ⚙';
		font-size: 13px;
		color: #999;
		cursor: default;
	}

	.builder__editor-content :global(.cm-dg-data-attr:hover)::after {
		content: attr(title);
		position: absolute;
		left: 0;
		top: 100%;
		z-index: 10;
		padding: 4px 8px;
		font-size: 12px;
		font-family: monospace;
		white-space: nowrap;
		color: #fff;
		background: #333;
		border-radius: 4px;
		pointer-events: none;
	}

	.builder__preview {
		flex: 1;
		display: flex;
		flex-direction: column;
		min-height: 0;
	}

	.builder__message {
		display: flex;
		align-items: center;
		justify-content: center;
		height: 100vh;
		font-size: 14px;
		color: #666;
	}

	.builder__message--error {
		color: #c00;
	}

	.builder__preview-iframe {
		flex: 1;
		min-height: 0;
		width: 100%;
		border: none;
		background: white;
	}
</style>
