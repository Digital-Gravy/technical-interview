<script lang="ts">
	interface Props {
		level?: number;
		content?: string;
		htmlAttributes?: Record<string, string>;
	}

	let { level = 2, content = '', htmlAttributes = {} }: Props = $props();

	let effectiveLevel = $derived(level >= 1 && level <= 6 ? level : 2);
</script>

<svelte:element this={`h${effectiveLevel}`} {...htmlAttributes}>
	{content}
</svelte:element>
