<script lang="ts">
	interface Props {
		content?: string;
		htmlAttributes?: Record<string, string>;
	}

	let { content = '', htmlAttributes = {} }: Props = $props();
</script>

<p {...htmlAttributes}>
	{content}
</p>
