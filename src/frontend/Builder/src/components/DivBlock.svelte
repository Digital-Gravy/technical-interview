<script lang="ts">
	import type { Snippet } from 'svelte';

	interface Props {
		className?: string;
		htmlAttributes?: Record<string, string>;
		children?: Snippet;
	}

	let { className = '', htmlAttributes = {}, children }: Props = $props();
</script>

<div class={className || undefined} {...htmlAttributes}>
	{@render children?.()}
</div>
