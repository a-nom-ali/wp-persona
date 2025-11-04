<?php

namespace Ai_Persona\Admin;

use Ai_Persona\Capabilities;
use Ai_Persona\Logging;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the analytics submenu page.
 */
function register_analytics_page() {
	$caps         = Capabilities\get_persona_capabilities();
	$required_cap = isset( $caps['edit_posts'] ) ? $caps['edit_posts'] : 'edit_ai_personas';

	add_submenu_page(
		'edit.php?post_type=ai_persona',
		__( 'Persona Analytics', 'ai-persona' ),
		__( 'Analytics', 'ai-persona' ),
		$required_cap,
		'ai-persona-analytics',
		__NAMESPACE__ . '\\render_analytics_page'
	);
}
add_action( 'admin_menu', __NAMESPACE__ . '\\register_analytics_page' );

/**
 * Render the analytics dashboard.
 */
function render_analytics_page() {
	if ( ! Logging\is_enabled() ) {
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Persona Analytics', 'ai-persona' ) . '</h1>';
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'Analytics logging is disabled. Enable the "Analytics & Logging" setting to view dashboard metrics.', 'ai-persona' ) . '</p></div>';
		echo '</div>';
		return;
	}

	$summary        = Logging\get_log_summary();
	$recent         = Logging\get_recent_log_entries( 20 );
	$persona_titles = array();

	foreach ( array_keys( $summary['personas'] ) as $persona_id ) {
		if ( ! $persona_id ) {
			continue;
		}
		$post = get_post( $persona_id );
		if ( $post && 'ai_persona' === $post->post_type ) {
			$persona_titles[ $persona_id ] = $post->post_title ? $post->post_title : ( '#' . $persona_id );
		}
	}

	foreach ( $recent as $entry ) {
		$persona_id = $entry['persona_id'];
		if ( $persona_id && ! isset( $persona_titles[ $persona_id ] ) ) {
			$post = get_post( $persona_id );
			if ( $post && 'ai_persona' === $post->post_type ) {
				$persona_titles[ $persona_id ] = $post->post_title ? $post->post_title : ( '#' . $persona_id );
			}
		}
	}

	$date_format = get_option( 'date_format', 'Y-m-d' ) . ' ' . get_option( 'time_format', 'H:i' );
	?>
	<div class="wrap ai-persona-analytics">
		<h1><?php esc_html_e( 'Persona Analytics', 'ai-persona' ); ?></h1>

		<p class="description">
			<?php esc_html_e( 'Metrics are generated from the persona log file (`wp-content/uploads/ai-persona/persona.log`). User inputs are truncated to protect sensitive content.', 'ai-persona' ); ?>
		</p>

		<div class="ai-persona-analytics__cards">
			<div class="ai-persona-analytics__card">
				<span class="ai-persona-analytics__card-label"><?php esc_html_e( 'Total Interactions', 'ai-persona' ); ?></span>
				<strong class="ai-persona-analytics__card-value"><?php echo esc_html( number_format_i18n( $summary['total_events'] ) ); ?></strong>
			</div>
			<div class="ai-persona-analytics__card">
				<span class="ai-persona-analytics__card-label"><?php esc_html_e( 'Unique Personas', 'ai-persona' ); ?></span>
				<strong class="ai-persona-analytics__card-value"><?php echo esc_html( number_format_i18n( $summary['unique_personas'] ) ); ?></strong>
			</div>
			<div class="ai-persona-analytics__card">
				<span class="ai-persona-analytics__card-label"><?php esc_html_e( 'Events (Last 24h)', 'ai-persona' ); ?></span>
				<strong class="ai-persona-analytics__card-value"><?php echo esc_html( number_format_i18n( $summary['last_24_hours'] ) ); ?></strong>
			</div>
			<div class="ai-persona-analytics__card">
				<span class="ai-persona-analytics__card-label"><?php esc_html_e( 'Avg Prompt Length', 'ai-persona' ); ?></span>
				<strong class="ai-persona-analytics__card-value"><?php echo esc_html( number_format_i18n( $summary['avg_prompt_chars'] ) ); ?></strong>
				<span class="ai-persona-analytics__card-sublabel"><?php esc_html_e( 'characters', 'ai-persona' ); ?></span>
			</div>
		</div>

		<div class="ai-persona-analytics__grid">
			<div class="ai-persona-analytics__panel">
				<h2><?php esc_html_e( 'Providers', 'ai-persona' ); ?></h2>
				<?php if ( ! empty( $summary['providers'] ) ) : ?>
					<table class="ai-persona-analytics__table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Provider', 'ai-persona' ); ?></th>
								<th><?php esc_html_e( 'Interactions', 'ai-persona' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $summary['providers'] as $provider => $count ) : ?>
								<tr>
									<td><?php echo esc_html( ucfirst( $provider ) ); ?></td>
									<td class="ai-persona-analytics__table-num"><?php echo esc_html( number_format_i18n( $count ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p><?php esc_html_e( 'No provider activity recorded yet.', 'ai-persona' ); ?></p>
				<?php endif; ?>
			</div>
			<div class="ai-persona-analytics__panel">
				<h2><?php esc_html_e( 'Top Personas', 'ai-persona' ); ?></h2>
				<?php if ( ! empty( $summary['personas'] ) ) : ?>
					<table class="ai-persona-analytics__table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Persona', 'ai-persona' ); ?></th>
								<th><?php esc_html_e( 'Interactions', 'ai-persona' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( array_slice( $summary['personas'], 0, 10, true ) as $persona_id => $count ) : ?>
								<tr>
									<td>
										<?php
										if ( ! $persona_id ) {
											esc_html_e( 'Unassigned', 'ai-persona' );
										} elseif ( isset( $persona_titles[ $persona_id ] ) ) {
											echo esc_html( $persona_titles[ $persona_id ] );
										} else {
											printf( esc_html__( 'Persona #%d', 'ai-persona' ), $persona_id );
										}
										?>
									</td>
									<td class="ai-persona-analytics__table-num"><?php echo esc_html( number_format_i18n( $count ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p><?php esc_html_e( 'No persona usage recorded yet.', 'ai-persona' ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<h2><?php esc_html_e( 'Recent Activity', 'ai-persona' ); ?></h2>
		<?php if ( ! empty( $recent ) ) : ?>
			<table class="ai-persona-analytics__table ai-persona-analytics__table--wide">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Timestamp', 'ai-persona' ); ?></th>
						<th><?php esc_html_e( 'Persona', 'ai-persona' ); ?></th>
						<th><?php esc_html_e( 'Provider', 'ai-persona' ); ?></th>
						<th><?php esc_html_e( 'Prompt Length', 'ai-persona' ); ?></th>
						<th><?php esc_html_e( 'User Input Preview', 'ai-persona' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $recent as $entry ) : ?>
						<tr>
							<td>
								<?php
								$timestamp = isset( $entry['timestamp'] ) ? strtotime( $entry['timestamp'] ) : false;
								if ( $timestamp ) {
									echo esc_html( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $timestamp ), $date_format ) );
								} else {
									esc_html_e( 'Unknown', 'ai-persona' );
								}
								?>
							</td>
							<td>
								<?php
								$persona_id = $entry['persona_id'];
								if ( ! $persona_id ) {
									esc_html_e( 'Unassigned', 'ai-persona' );
								} elseif ( isset( $persona_titles[ $persona_id ] ) ) {
									echo esc_html( $persona_titles[ $persona_id ] );
								} else {
									printf( esc_html__( 'Persona #%d', 'ai-persona' ), $persona_id );
								}
								?>
							</td>
							<td><?php echo esc_html( ucfirst( $entry['provider'] ) ); ?></td>
							<td class="ai-persona-analytics__table-num"><?php echo esc_html( number_format_i18n( $entry['prompt_len'] ) ); ?></td>
							<td><?php echo esc_html( $entry['preview'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><?php esc_html_e( 'No recent activity captured yet.', 'ai-persona' ); ?></p>
		<?php endif; ?>

		<p class="description ai-persona-analytics__privacy">
			<?php esc_html_e( 'Need deeper insights? Export the log file and process it in your preferred analytics tool. Respect user privacy when storing or sharing transcripts.', 'ai-persona' ); ?>
		</p>
	</div>
	<?php
}
