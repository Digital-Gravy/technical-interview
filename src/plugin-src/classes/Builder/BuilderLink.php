<?php
/**
 * Builder Link — adds "Edit in Builder" entry points and serves the builder shell.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Builder;

use DgInterview\Expressions\DynamicContentProcessor;
/**
 * Registers row actions and template redirect for the builder UI.
 */
class BuilderLink {

	/**
	 * Builder shell view renderer.
	 *
	 * @var BuilderShellView
	 */
	private BuilderShellView $shell_view;

	/**
	 * Hook into WordPress.
	 *
	 * @param BuilderShellView|null $shell_view Optional shell view override.
	 */
	public function __construct( ?BuilderShellView $shell_view = null ) {
		$this->shell_view = $shell_view ?? new BuilderShellView();
		add_filter( 'page_row_actions', array( $this, 'add_row_action' ), 10, 2 );
		add_action( 'template_redirect', array( $this, 'maybe_load_builder' ) );
	}

	/**
	 * Whether the current request is a builder request.
	 *
	 * @return bool
	 */
	private function is_builder_request(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET['dg-builder'] ) && '1' === $_GET['dg-builder'];
	}

	/**
	 * Add "Edit in Builder" link to the Pages list row actions.
	 *
	 * @param array<string, string> $actions Existing row actions.
	 * @param \WP_Post              $post    The post object.
	 * @return array<string, string>
	 */
	public function add_row_action( array $actions, \WP_Post $post ): array {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $actions;
		}

		$edit_url = add_query_arg(
			array(
				'dg-builder' => '1',
				'post_id'    => $post->ID,
			),
			home_url( '/' )
		);

		$actions['edit_in_builder'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $edit_url ),
			__( 'Edit in Builder', 'dg-interview' )
		);

		return $actions;
	}

	/**
	 * Intercept the request and load the builder shell if applicable.
	 *
	 * @return void
	 */
	public function maybe_load_builder(): void {
		if ( ! $this->is_builder_request() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
		$assets  = $this->get_builder_asset_tags();
		$html    = $this->shell_view->render(
			$assets,
			array(
				'postId'             => $post_id,
				'restUrl'            => esc_url_raw( rest_url( 'wp/v2/pages/' ) ),
				'saveUrl'            => esc_url_raw( rest_url( 'dg-interview/v1/posts/' ) ),
				'nonce'              => wp_create_nonce( 'wp_rest' ),
				'sources'            => DynamicContentProcessor::get_post_sources_for_builder( $post_id ),
				'languageAttributes' => $this->get_language_attributes(),
				'charset'            => (string) get_bloginfo( 'charset' ),
			)
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML document built from trusted internal values.
		echo $html;
		exit;
	}

	/**
	 * Capture language attributes output as a string.
	 *
	 * @return string
	 */
	private function get_language_attributes(): string {
		ob_start();
		language_attributes();
		$attrs = ob_get_clean();
		return is_string( $attrs ) ? trim( $attrs ) : '';
	}

	/**
	 * Build script and style tags for the builder app.
	 *
	 * Uses the Vite manifest in production, or the Vite dev server in development.
	 *
	 * @return array{js: string, css: string}
	 */
	private function get_builder_asset_tags(): array {
		if ( defined( 'DG_VITE_DEV' ) && DG_VITE_DEV ) {
			return $this->get_dev_tags();
		}

		$dist_path = plugin_dir_path( __DIR__ ) . 'builder-dist/.vite/manifest.json';

		if ( file_exists( $dist_path ) ) {
			return $this->get_production_tags( $dist_path );
		}

		return $this->get_dev_tags();
	}

	/**
	 * Build asset tags from the Vite manifest (production).
	 *
	 * @param string $manifest_path Path to the manifest.json file.
	 * @return array{js: string, css: string}
	 */
	private function get_production_tags( string $manifest_path ): array {
		$result = array(
			'js'  => '',
			'css' => '',
		);

		$manifest_contents = file_get_contents( $manifest_path );
		if ( false === $manifest_contents ) {
			return $result;
		}

		$manifest = json_decode( $manifest_contents, true );
		if ( ! is_array( $manifest ) || ! isset( $manifest['src/main.ts']['file'] ) ) {
			return $result;
		}

		$entry = $manifest['src/main.ts'];
		$file  = $entry['file'];
		if ( ! is_string( $file ) ) {
			return $result;
		}

		// phpcs:disable WordPress.WP.EnqueuedResources -- outputting tags directly, no wp_head/wp_footer.
		$base_url = plugins_url( 'builder-dist/', __DIR__ . '/../dg-interview.php' );
		$result['js'] = sprintf(
			'<script type="module" src="%s"></script>',
			esc_url( $base_url . $file )
		);

		if ( is_array( $entry ) && isset( $entry['css'] ) && is_array( $entry['css'] ) ) {
			foreach ( $entry['css'] as $css_file ) {
				if ( ! is_string( $css_file ) ) {
					continue;
				}
				$result['css'] .= sprintf(
					'<link rel="stylesheet" href="%s">',
					esc_url( $base_url . $css_file )
				);
			}
		}
		// phpcs:enable

		return $result;
	}

	/**
	 * Build asset tags for the Vite dev server (development).
	 *
	 * @return array{js: string, css: string}
	 */
	private function get_dev_tags(): array {
		// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript -- bypassing wp_head/wp_footer intentionally.
		return array(
			'js'  => '<script type="module" src="http://localhost:5179/@vite/client"></script>'
				. '<script type="module" src="http://localhost:5179/src/main.ts"></script>',
			'css' => '',
		);
		// phpcs:enable
	}
}
