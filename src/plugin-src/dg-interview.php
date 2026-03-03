<?php
/**
 * Plugin Name: DG Interview
 * Description: Technical interview project — a simplified page builder plugin.
 * Version: 1.0.0
 * Requires PHP: 8.1
 *
 * @package DgInterview
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/vendor/autoload.php';

$registry = new DgInterview\Blocks\BlockRegistry();
$registry->register( new DgInterview\Blocks\DivBlock() );
$registry->register( new DgInterview\Blocks\HeadingBlock() );
$registry->register( new DgInterview\Blocks\ParagraphBlock() );

new DgInterview\Blocks\BlockAnnotator();
new DgInterview\Builder\BuilderLink();
new DgInterview\Builder\BuilderSaveRoute( $registry );
new DgInterview\Expressions\DynamicContentFilter();
new DgInterview\BlockEditorIntegration();

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	\WP_CLI::add_command( 'dg-interview', DgInterview\Builder\CliCommand::class );
}
