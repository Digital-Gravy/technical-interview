<?php
/**
 * Builder Shell View — renders the HTML shell for the builder app.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Builder;

/**
 * Presentation-only view for the builder shell document.
 */
class BuilderShellView {

	/**
	 * Render the full builder HTML document.
	 *
	 * @param array<string, string> $assets Asset tags.
	 * @param array<string, mixed>  $config Builder shell config.
	 * @phpstan-param array{js: string, css: string} $assets
	 * @phpstan-param array{
	 *   postId: int,
	 *   restUrl: string,
	 *   saveUrl: string,
	 *   nonce: string,
	 *   sources: array<array{key: string, source: array<string, mixed>}>,
	 *   languageAttributes: string,
	 *   charset: string
	 * } $config
	 * @return string
	 */
	public function render( array $assets, array $config ): string {
		$builder_config = array(
			'postId'  => $config['postId'],
			'restUrl' => $config['restUrl'],
			'saveUrl' => $config['saveUrl'],
			'nonce'   => $config['nonce'],
			'sources' => $config['sources'],
		);
		$builder_config_json = wp_json_encode( $builder_config );
		if ( false === $builder_config_json ) {
			$builder_config_json = '{}';
		}

		return sprintf(
			"<!DOCTYPE html>\n"
			. "<html %1\$s>\n"
			. "<head>\n"
			. "\t<meta charset=\"%2\$s\">\n"
			. "\t<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n"
			. "\t%3\$s\n"
			. "</head>\n"
			. "<body>\n"
			. "\t<div id=\"dg-builder\"></div>\n"
			. "\t<script>\n"
			. "\t\twindow.dgBuilderConfig = %4\$s;\n"
			. "\t</script>\n"
			. "\t%5\$s\n"
			. "</body>\n"
			. "</html>\n",
			$config['languageAttributes'],
			esc_attr( $config['charset'] ),
			$assets['css'],
			$builder_config_json,
			$assets['js']
		);
	}
}
