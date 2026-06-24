<?php
/**
 * Postwave admin page template — v3 Professional
 * Variables: $options, $stats, $entries, $tab, $is_setup, $retry_count
 *
 * @package Postwave
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$o   = function( $k, $d = '' ) use ( $options ) { return isset( $options[ $k ] ) ? $options[ $k ] : $d; };
$url = function( array $args ) { return esc_url( add_query_arg( $args, admin_url( 'admin.php' ) ) ); };
$act = esc_url( admin_url( 'admin-post.php' ) );

$configured = ! empty( $options['server_url'] ) && ! empty( $options['username'] ) && ! empty( $options['password'] );
$enabled    = ! empty( $options['enabled'] );

/* ── Inline SVGs ── */
$logo = '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
  <defs><linearGradient id="pwG" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
    <stop offset="0%" stop-color="#818cf8"/><stop offset="100%" stop-color="#4338ca"/>
  </linearGradient></defs>
  <rect width="32" height="32" rx="8" fill="url(#pwG)"/>
  <rect x="7" y="17" width="18" height="9" rx="2" fill="white" opacity=".95"/>
  <path d="M7 19L16 23.5L25 19" stroke="#6366f1" stroke-width="1.3" stroke-linejoin="round" fill="none"/>
  <path d="M10 14.5Q13 10.5 16 14.5Q19 18.5 22 14.5" stroke="white" stroke-width="1.7" stroke-linecap="round" fill="none" opacity=".9"/>
  <path d="M12 11.5Q14 9 16 11.5Q18 14 20 11.5" stroke="white" stroke-width="1.3" stroke-linecap="round" fill="none" opacity=".55"/>
</svg>';

/* icon helpers */
$icon_general    = '<svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>';
$icon_connection = '<svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M2 5a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm14 1a1 1 0 11-2 0 1 1 0 012 0zM2 13a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2zm14 1a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"/></svg>';
$icon_log        = '<svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>';
$icon_check      = '<svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>';
?>

<!-- ══════════════ WIZARD OVERLAY ══════════════ -->
<div id="pw-wizard" class="pw-wizard-overlay">
  <div class="pw-wizard__card">
    <div class="pw-wizard__header">
      <div class="pw-wizard__logo">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" width="28" height="28"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
      </div>
      <h1 class="pw-wizard__title"><?php esc_html_e( 'Welcome to Postwave', 'postwave' ); ?></h1>
      <p class="pw-wizard__sub"><?php esc_html_e( 'Modern email for WordPress', 'postwave' ); ?></p>
      <div class="pw-wizard__steps">
        <div class="pw-wizard__step-dot pw-wizard__step-dot--active"></div>
        <div class="pw-wizard__step-dot"></div>
        <div class="pw-wizard__step-dot"></div>
        <div class="pw-wizard__step-dot"></div>
      </div>
    </div>
    <div class="pw-wizard__body">

      <!-- Step 0: Welcome -->
      <div class="pw-wizard__step pw-wizard__step--active">
        <p style="font-size:15px;color:#374151;margin:0 0 20px;"><?php esc_html_e( 'Send every WordPress email through your own JMAP mail server — no SMTP ports, no relay limits.', 'postwave' ); ?></p>
        <ul style="list-style:none;padding:0;margin:0 0 24px;display:flex;flex-direction:column;gap:12px;">
          <li style="display:flex;align-items:flex-start;gap:10px;">
            <svg viewBox="0 0 20 20" fill="#6366f1" width="18" height="18" style="flex-shrink:0;margin-top:2px;"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span style="font-size:13px;color:#374151;"><?php esc_html_e( 'RFC 8620/8621 — works with Stalwart, Fastmail &amp; more', 'postwave' ); ?></span>
          </li>
          <li style="display:flex;align-items:flex-start;gap:10px;">
            <svg viewBox="0 0 20 20" fill="#6366f1" width="18" height="18" style="flex-shrink:0;margin-top:2px;"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span style="font-size:13px;color:#374151;"><?php esc_html_e( 'Multi-account routing — send order emails from a dedicated address', 'postwave' ); ?></span>
          </li>
          <li style="display:flex;align-items:flex-start;gap:10px;">
            <svg viewBox="0 0 20 20" fill="#6366f1" width="18" height="18" style="flex-shrink:0;margin-top:2px;"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span style="font-size:13px;color:#374151;"><?php esc_html_e( 'Retry queue, open tracking, and full mail log included', 'postwave' ); ?></span>
          </li>
        </ul>
        <div class="pw-wizard__actions">
          <button type="button" class="pw-wizard__btn pw-wizard__btn--ghost" data-wizard-skip><?php esc_html_e( 'Skip for now', 'postwave' ); ?></button>
          <button type="button" class="pw-wizard__btn pw-wizard__btn--primary" data-wizard-next="1"><?php esc_html_e( 'Get Started', 'postwave' ); ?> &rarr;</button>
        </div>
      </div>

      <!-- Step 1: Connect -->
      <div class="pw-wizard__step">
        <form method="post" action="<?php echo $act; ?>" id="pw-wz-form">
          <?php wp_nonce_field( 'postwave_save' ); ?>
          <input type="hidden" name="action"         value="postwave_save">
          <input type="hidden" name="postwave[_tab]" value="general">
          <div class="pw-wizard__field">
            <label for="pw-wz-url"><?php esc_html_e( 'Server URL', 'postwave' ); ?></label>
            <input type="url" id="pw-wz-url" name="postwave[server_url]"
              value="<?php echo esc_attr( $o( 'server_url' ) ); ?>"
              placeholder="https://mail.example.com" required>
            <span style="font-size:12px;color:#6b7280;margin-top:4px;display:block;"><?php esc_html_e( 'JMAP session is auto-discovered at /.well-known/jmap', 'postwave' ); ?></span>
          </div>
          <div class="pw-wizard__field">
            <label for="pw-wz-user"><?php esc_html_e( 'Username', 'postwave' ); ?></label>
            <input type="text" id="pw-wz-user" name="postwave[username]"
              value="<?php echo esc_attr( $o( 'username' ) ); ?>" autocomplete="username" required>
          </div>
          <div class="pw-wizard__field">
            <label for="pw-wz-pass"><?php esc_html_e( 'Password', 'postwave' ); ?></label>
            <input type="password" id="pw-wz-pass" name="postwave[password]" autocomplete="new-password">
          </div>
          <div class="pw-wizard__actions">
            <button type="button" class="pw-wizard__btn pw-wizard__btn--ghost" data-wizard-next="0">&larr; <?php esc_html_e( 'Back', 'postwave' ); ?></button>
            <button type="button" class="pw-wizard__btn pw-wizard__btn--primary" data-wizard-next="2"><?php esc_html_e( 'Test &amp; Continue', 'postwave' ); ?> &rarr;</button>
          </div>
        </form>
      </div>

      <!-- Step 2: Identity -->
      <div class="pw-wizard__step">
        <div class="pw-wizard__field">
          <label for="pw-wz-iname"><?php esc_html_e( 'Identity Name', 'postwave' ); ?></label>
          <input type="text" id="pw-wz-iname" name="postwave[identity_name]"
            value="<?php echo esc_attr( $o( 'identity_name', get_bloginfo( 'name' ) ) ); ?>">
        </div>
        <div class="pw-wizard__field">
          <label for="pw-wz-iemail"><?php esc_html_e( 'Identity Email', 'postwave' ); ?></label>
          <input type="email" id="pw-wz-iemail" name="postwave[identity_email]"
            value="<?php echo esc_attr( $o( 'identity_email', get_bloginfo( 'admin_email' ) ) ); ?>">
        </div>
        <div class="pw-wizard__field">
          <label for="pw-wz-iid"><?php esc_html_e( 'Identity ID', 'postwave' ); ?> <span style="font-size:11px;color:#9ca3af;"><?php esc_html_e( 'optional — leave blank to auto-resolve', 'postwave' ); ?></span></label>
          <input type="text" id="pw-wz-iid" name="postwave[identity_id]"
            value="<?php echo esc_attr( $o( 'identity_id' ) ); ?>"
            placeholder="<?php esc_attr_e( 'Auto-resolve from server', 'postwave' ); ?>">
        </div>
        <div class="pw-wizard__actions">
          <button type="button" class="pw-wizard__btn pw-wizard__btn--ghost" data-wizard-next="1">&larr; <?php esc_html_e( 'Back', 'postwave' ); ?></button>
          <button type="button" class="pw-wizard__btn pw-wizard__btn--primary" data-wizard-next="3"><?php esc_html_e( 'Save &amp; Continue', 'postwave' ); ?> &rarr;</button>
        </div>
      </div>

      <!-- Step 3: Done -->
      <div class="pw-wizard__step">
        <div class="pw-wizard__success">
          <div class="pw-wizard__check">&#10003;</div>
          <h2 style="font-size:20px;font-weight:700;color:#1d2327;margin:0 0 8px;"><?php esc_html_e( 'Postwave is ready!', 'postwave' ); ?></h2>
          <p style="font-size:14px;color:#6b7280;margin:0 0 20px;"><?php esc_html_e( 'Your JMAP server is configured. Enable Postwave on the General tab to start routing emails.', 'postwave' ); ?></p>
          <ul style="list-style:none;padding:0;margin:0 0 24px;text-align:left;display:inline-block;">
            <li style="font-size:13px;color:#374151;margin-bottom:8px;">&#10003; <?php esc_html_e( 'Server connected', 'postwave' ); ?></li>
            <li style="font-size:13px;color:#374151;margin-bottom:8px;">&#10003; <?php esc_html_e( 'Identity configured', 'postwave' ); ?></li>
            <li style="font-size:13px;color:#374151;">&#10003; <?php esc_html_e( 'Ready to send', 'postwave' ); ?></li>
          </ul>
        </div>
        <div class="pw-wizard__actions" style="justify-content:center;">
          <button type="button" class="pw-wizard__btn pw-wizard__btn--primary" data-wizard-finish><?php esc_html_e( 'Go to Dashboard', 'postwave' ); ?></button>
        </div>
      </div>

    </div><!-- /.pw-wizard__body -->
  </div><!-- /.pw-wizard__card -->
</div><!-- #pw-wizard -->
<?php if ( $is_setup ) : ?>
<script>document.getElementById('pw-wizard').classList.add('pw-wizard--active');</script>
<?php endif; ?>

<?php /* ══════════════ DASHBOARD ══════════════ */ ?>

<div id="pw-page" class="pw-mode-dash">
<div id="pw-toast" class="pw-toast" aria-live="polite" aria-atomic="true"></div>

  <?php
  /* ── Tab meta ── */
  $icon_accounts = '<svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>';
  $icon_routing  = '<svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>';

  $tab_meta = array(
    'general'    => array(
      'label' => __( 'General', 'postwave' ),
      'desc'  => __( 'Enable Postwave JMAP and configure sender information.', 'postwave' ),
      'icon'  => $icon_general,
    ),
    'accounts'   => array(
      'label' => __( 'Accounts', 'postwave' ),
      'desc'  => __( 'Configure multiple JMAP accounts for routing.', 'postwave' ),
      'icon'  => $icon_accounts,
    ),
    'routing'    => array(
      'label' => __( 'Routing', 'postwave' ),
      'desc'  => __( 'Route emails to specific accounts based on conditions.', 'postwave' ),
      'icon'  => $icon_routing,
    ),
    'connection' => array(
      'label' => __( 'Connection', 'postwave' ),
      'desc'  => __( 'JMAP server credentials and connection testing.', 'postwave' ),
      'icon'  => $icon_connection,
    ),
    'log'        => array(
      'label' => __( 'Mail Log', 'postwave' ),
      'desc'  => __( 'Last 100 send attempts. Message bodies are never stored.', 'postwave' ),
      'icon'  => $icon_log,
    ),
  );
  $current_meta = isset( $tab_meta[ $tab ] ) ? $tab_meta[ $tab ] : $tab_meta['general'];
  ?>

  <!-- ── App Header ── -->
  <header class="pw-app-header">
    <div class="pw-app-brand">
      <?php echo $logo; ?>
      <div class="pw-app-brand-text">
        <strong>Postwave</strong>
        <span>JMAP Mail for WordPress</span>
      </div>
    </div>
    <div class="pw-app-header-right">
      <?php
      if ( $enabled && $configured ) {
        echo '<span class="pw-status pw-status-active"><i></i>' . esc_html__( 'Active', 'postwave' ) . '</span>';
      } elseif ( $configured ) {
        echo '<span class="pw-status pw-status-inactive"><i></i>' . esc_html__( 'Disabled', 'postwave' ) . '</span>';
      } else {
        echo '<span class="pw-status pw-status-unconfigured"><i></i>' . esc_html__( 'Not configured', 'postwave' ) . '</span>';
      }
      ?>
    </div>
  </header>

  <!-- ── Top Navigation ── -->
  <nav class="pw-app-nav">
    <div class="pw-app-nav-inner">
      <?php foreach ( $tab_meta as $key => $meta ) :
        $is_active = $tab === $key;
        $count = '';
        if ( 'log' === $key && $stats['total'] > 0 ) {
          $count = '<span class="pw-nav-count">' . intval( $stats['total'] ) . '</span>';
        } elseif ( 'accounts' === $key && count( $accounts ) > 1 ) {
          $count = '<span class="pw-nav-count">' . count( $accounts ) . '</span>';
        } elseif ( 'routing' === $key && count( $rules ) > 0 ) {
          $count = '<span class="pw-nav-count">' . count( $rules ) . '</span>';
        }
      ?>
      <a href="<?php echo $url( array( 'page' => 'postwave', 'tab' => $key ) ); ?>"
         class="pw-nav-item<?php echo $is_active ? ' pw-nav-item-active' : ''; ?>">
        <?php echo $meta['icon']; ?>
        <span><?php echo esc_html( $meta['label'] ); ?></span>
        <?php echo $count; ?>
      </a>
      <?php endforeach; ?>
    </div>
    <span class="pw-app-version">v<?php echo esc_html( POSTWAVE_VERSION ); ?></span>
  </nav>

  <!-- ── Main Content ── -->
  <div class="pw-app-body">
    <main class="pw-main">

      <!-- Page title + saved notice -->
      <div class="pw-page-header">
        <div>
          <h2><?php echo esc_html( $current_meta['label'] ); ?></h2>
          <p><?php echo esc_html( $current_meta['desc'] ); ?></p>
        </div>
      </div>

      <?php if ( isset( $_GET['saved'] ) ) : ?>
      <div class="pw-notice pw-notice-success">
        <?php echo $icon_check; ?>
        <?php esc_html_e( 'Settings saved successfully.', 'postwave' ); ?>
      </div>
      <?php endif; ?>

      <?php if ( isset( $_GET['cleared'] ) ) : ?>
      <div class="pw-notice pw-notice-success">
        <?php echo $icon_check; ?>
        <?php esc_html_e( 'Mail log cleared.', 'postwave' ); ?>
      </div>
      <?php endif; ?>

      <!-- Stats row (always visible) -->
      <div class="pw-stats-row">
        <?php
        $stats_cfg = [
          [ 'value' => $stats['sent_today'],   'label' => __( 'Sent today', 'postwave' ),       'color' => 'blue' ],
          [ 'value' => $stats['failed_today'], 'label' => __( 'Failed today', 'postwave' ),     'color' => 'red'  ],
          [ 'value' => $stats['sent_week'],    'label' => __( 'Sent this week', 'postwave' ),   'color' => 'blue' ],
          [ 'value' => $stats['failed_week'],  'label' => __( 'Failed this week', 'postwave' ), 'color' => 'red'  ],
          [ 'value' => $stats['total'],        'label' => __( 'Total logged', 'postwave' ),     'color' => 'gray' ],
        ];
        foreach ( $stats_cfg as $s ) : ?>
        <div class="pw-stat pw-stat-<?php echo esc_attr( $s['color'] ); ?>">
          <strong><?php echo esc_html( $s['value'] ); ?></strong>
          <span><?php echo esc_html( $s['label'] ); ?></span>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- ══ TAB: GENERAL ══ -->
      <?php if ( $tab === 'general' ) : ?>

      <div class="pw-status-bar">
        <div class="pw-status-item">
          <span class="pw-status-num"><?php echo esc_html( $stats['sent_today'] ); ?></span>
          <span class="pw-status-label"><?php esc_html_e( 'Sent today', 'postwave' ); ?></span>
        </div>
        <div class="pw-status-item">
          <span class="pw-status-num"><?php echo esc_html( $stats['sent_week'] ); ?></span>
          <span class="pw-status-label"><?php esc_html_e( 'Sent this week', 'postwave' ); ?></span>
        </div>
        <div class="pw-status-item pw-status-item--<?php echo $stats['failed_today'] > 0 ? 'warn' : 'ok'; ?>">
          <span class="pw-status-num"><?php echo esc_html( $stats['failed_today'] ); ?></span>
          <span class="pw-status-label"><?php esc_html_e( 'Failed today', 'postwave' ); ?></span>
        </div>
        <div class="pw-status-item">
          <span class="pw-status-num"><?php echo esc_html( $stats['total'] ); ?></span>
          <span class="pw-status-label"><?php esc_html_e( 'Total logged', 'postwave' ); ?></span>
        </div>
      </div>

      <form method="post" action="<?php echo $act; ?>">
        <?php wp_nonce_field( 'postwave_save' ); ?>
        <input type="hidden" name="action"          value="postwave_save">
        <input type="hidden" name="postwave[_tab]"  value="general">
        <!-- Preserve connection fields -->
        <input type="hidden" name="postwave[server_url]"       value="<?php echo esc_attr( $o( 'server_url' ) ); ?>">
        <input type="hidden" name="postwave[username]"         value="<?php echo esc_attr( $o( 'username' ) ); ?>">
        <!-- Preserve v1.1 connection-tab fields -->
        <input type="hidden" name="postwave[identity_id]"      value="<?php echo esc_attr( $o( 'identity_id' ) ); ?>">
        <input type="hidden" name="postwave[identity_name]"    value="<?php echo esc_attr( $o( 'identity_name' ) ); ?>">
        <input type="hidden" name="postwave[identity_email]"   value="<?php echo esc_attr( $o( 'identity_email' ) ); ?>">

        <!-- Enable card -->
        <div class="pw-panel">
          <div class="pw-panel-body">
            <input type="hidden" name="postwave[enabled]" value="0">
            <div class="pw-toggle-row">
              <div class="pw-toggle-info">
                <strong><?php esc_html_e( 'Enable Postwave JMAP', 'postwave' ); ?></strong>
                <span><?php esc_html_e( 'Route all WordPress emails through your JMAP server', 'postwave' ); ?></span>
              </div>
              <label class="pw-toggle">
                <input type="checkbox" name="postwave[enabled]" value="1" class="pw-toggle-cb" <?php checked( 1, $o( 'enabled' ) ); ?>>
                <span class="pw-toggle-track"><span class="pw-toggle-thumb"></span></span>
              </label>
            </div>
          </div>
        </div>

        <!-- Sender info card -->
        <div class="pw-panel">
          <div class="pw-panel-header">
            <h3><?php esc_html_e( 'Sender Information', 'postwave' ); ?></h3>
            <p><?php esc_html_e( 'The name and email address shown to recipients of outgoing mail.', 'postwave' ); ?></p>
          </div>
          <div class="pw-panel-body">
            <div class="pw-row-2">
              <div class="pw-field">
                <label for="pw-from-name"><?php esc_html_e( 'From Name', 'postwave' ); ?></label>
                <input type="text" id="pw-from-name" class="pw-input" name="postwave[from_name]"
                  value="<?php echo esc_attr( $o( 'from_name', get_bloginfo( 'name' ) ) ); ?>">
              </div>
              <div class="pw-field">
                <label for="pw-from-email"><?php esc_html_e( 'From Email', 'postwave' ); ?></label>
                <input type="email" id="pw-from-email" class="pw-input" name="postwave[from_email]"
                  value="<?php echo esc_attr( $o( 'from_email', get_bloginfo( 'admin_email' ) ) ); ?>">
              </div>
            </div>
            <div class="pw-field pw-field-half">
              <label for="pw-test-recip">
                <?php esc_html_e( 'Test recipient', 'postwave' ); ?>
                <em class="pw-label-opt"><?php esc_html_e( 'for Send Test Email button', 'postwave' ); ?></em>
              </label>
              <input type="email" id="pw-test-recip" class="pw-input" name="postwave[test_recipient]"
                value="<?php echo esc_attr( $o( 'test_recipient' ) ); ?>"
                placeholder="<?php esc_attr_e( 'Falls back to From Email', 'postwave' ); ?>">
            </div>
          </div>
          <div class="pw-panel-footer">
            <button type="submit" class="pw-btn pw-btn-primary"><?php esc_html_e( 'Save settings', 'postwave' ); ?></button>
          </div>
        </div>

        <!-- Automatic Retry panel -->
        <div class="pw-panel">
          <div class="pw-panel-header">
            <h3><?php esc_html_e( 'Automatic Retry', 'postwave' ); ?></h3>
            <p><?php esc_html_e( 'Automatically re-send failed emails using WP-Cron with exponential backoff.', 'postwave' ); ?></p>
          </div>
          <div class="pw-panel-body">
            <input type="hidden" name="postwave[retry_enabled]" value="0">
            <div class="pw-field-row pw-field-row--flex">
              <div class="pw-field-info">
                <label class="pw-label"><strong><?php esc_html_e( 'Enable retry queue', 'postwave' ); ?></strong></label>
                <p class="pw-desc"><?php esc_html_e( 'Failed sends will be retried automatically in the background.', 'postwave' ); ?></p>
              </div>
              <label class="pw-toggle">
                <input type="checkbox" name="postwave[retry_enabled]" class="pw-toggle-cb" value="1" <?php checked( $options['retry_enabled'] ?? 0 ); ?>>
                <span class="pw-toggle-track"><span class="pw-toggle-thumb"></span></span>
              </label>
            </div>

            <div class="pw-field-row pw-retry-options <?php echo empty( $options['retry_enabled'] ) ? 'pw-hidden' : ''; ?>">
              <div class="pw-col-2">
                <label class="pw-label" for="pw-retry-max"><?php esc_html_e( 'Max retry attempts', 'postwave' ); ?></label>
                <select id="pw-retry-max" name="postwave[retry_max]" class="pw-select">
                  <?php foreach ( array( 1, 2, 3, 4, 5 ) as $n ) : ?>
                    <option value="<?php echo esc_attr( $n ); ?>" <?php selected( (int) ( $options['retry_max'] ?? 3 ), $n ); ?>><?php echo esc_html( $n ); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="pw-col-2">
                <label class="pw-label" for="pw-retry-delay"><?php esc_html_e( 'Initial retry delay', 'postwave' ); ?></label>
                <select id="pw-retry-delay" name="postwave[retry_delay]" class="pw-select">
                  <option value="300"  <?php selected( (int) ( $options['retry_delay'] ?? 300 ), 300 );  ?>><?php esc_html_e( '5 minutes', 'postwave' ); ?></option>
                  <option value="900"  <?php selected( (int) ( $options['retry_delay'] ?? 300 ), 900 );  ?>><?php esc_html_e( '15 minutes', 'postwave' ); ?></option>
                  <option value="1800" <?php selected( (int) ( $options['retry_delay'] ?? 300 ), 1800 ); ?>><?php esc_html_e( '30 minutes', 'postwave' ); ?></option>
                  <option value="3600" <?php selected( (int) ( $options['retry_delay'] ?? 300 ), 3600 ); ?>><?php esc_html_e( '1 hour', 'postwave' ); ?></option>
                </select>
                <p class="pw-desc"><?php esc_html_e( 'Delay doubles on each retry attempt (exponential backoff).', 'postwave' ); ?></p>
              </div>
            </div>

            <?php if ( $retry_count > 0 ) : ?>
            <div class="pw-notice pw-notice--info" style="margin-top:16px;">
              <?php printf(
                /* translators: %d: number of emails pending retry */
                esc_html( _n( '%d email is pending retry.', '%d emails are pending retry.', $retry_count, 'postwave' ) ),
                (int) $retry_count
              ); ?>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Open Tracking panel -->
        <div class="pw-panel">
          <div class="pw-panel-header">
            <h3><?php esc_html_e( 'Open Tracking', 'postwave' ); ?></h3>
            <p><?php esc_html_e( 'Track when recipients open emails sent by your site. Opt-in only — read the privacy note below.', 'postwave' ); ?></p>
          </div>
          <div class="pw-panel-body">
            <input type="hidden" name="postwave[tracking_enabled]" value="0">
            <div class="pw-field-row pw-field-row--flex">
              <div class="pw-field-info">
                <label class="pw-label"><strong><?php esc_html_e( 'Enable open tracking', 'postwave' ); ?></strong></label>
                <p class="pw-desc"><?php esc_html_e( 'Embeds a 1×1 tracking pixel in outgoing HTML emails. Plain-text emails are never tracked.', 'postwave' ); ?></p>
              </div>
              <label class="pw-toggle">
                <input type="checkbox" name="postwave[tracking_enabled]" class="pw-toggle-cb" value="1" <?php checked( $options['tracking_enabled'] ?? 0 ); ?>>
                <span class="pw-toggle-track"><span class="pw-toggle-thumb"></span></span>
              </label>
            </div>
            <div class="pw-notice pw-notice--warning" style="margin-top:16px;">
              <strong><?php esc_html_e( 'Privacy:', 'postwave' ); ?></strong>
              <?php esc_html_e( 'Open tracking records when an email is opened. You may need to disclose this in your privacy policy. No personal data is sent to external servers — tracking is handled entirely on your own WordPress installation.', 'postwave' ); ?>
            </div>
          </div>
          <div class="pw-panel-footer">
            <button type="submit" class="pw-btn pw-btn-primary"><?php esc_html_e( 'Save settings', 'postwave' ); ?></button>
          </div>
        </div>

      </form>

      <!-- ══ TAB: ACCOUNTS ══ -->
      <?php elseif ( $tab === 'accounts' ) : ?>

      <?php if ( isset( $_GET['deleted'] ) ) : ?>
      <div class="pw-notice pw-notice-success">
        <?php echo $icon_check; ?>
        <?php esc_html_e( 'Account deleted.', 'postwave' ); ?>
      </div>
      <?php endif; ?>

      <!-- Account cards grid -->
      <div class="pw-account-grid">
        <?php foreach ( $accounts as $acct ) :
          $status_class = is_null( $acct['last_test_ok'] ) ? 'pw-status-dot--none' : ( $acct['last_test_ok'] ? 'pw-status-dot--ok' : 'pw-status-dot--fail' );
          $acct_json    = esc_attr( wp_json_encode( array(
            'id'             => $acct['id'],
            'name'           => $acct['name'],
            'server_url'     => $acct['server_url'],
            'username'       => $acct['username'],
            'identity_id'    => $acct['identity_id'],
            'identity_name'  => $acct['identity_name'],
            'identity_email' => $acct['identity_email'],
          ) ) );
        ?>
        <div class="pw-account-card" data-account="<?php echo $acct_json; ?>">
          <div class="pw-account-card-header">
            <span class="pw-status-dot <?php echo esc_attr( $status_class ); ?>"></span>
            <span class="pw-account-name"><?php echo esc_html( $acct['name'] ); ?></span>
            <?php if ( ! empty( $acct['is_primary'] ) ) : ?>
            <span class="pw-badge--primary"><?php esc_html_e( 'Primary', 'postwave' ); ?></span>
            <?php endif; ?>
          </div>
          <div class="pw-account-url"><?php echo esc_html( $acct['server_url'] ? $acct['server_url'] : __( '(no URL set)', 'postwave' ) ); ?></div>
          <div class="pw-account-actions">
            <button type="button" class="pw-btn pw-btn-secondary pw-btn--sm pw-edit-account-btn">
              <?php esc_html_e( 'Edit', 'postwave' ); ?>
            </button>
            <?php if ( empty( $acct['is_primary'] ) ) : ?>
            <form method="post" action="<?php echo $act; ?>" style="display:inline;" onsubmit="return confirm('<?php echo esc_js( __( 'Delete this account?', 'postwave' ) ); ?>')">
              <?php wp_nonce_field( 'postwave_delete_account' ); ?>
              <input type="hidden" name="action"     value="postwave_delete_account">
              <input type="hidden" name="account_id" value="<?php echo esc_attr( $acct['id'] ); ?>">
              <button type="submit" class="pw-btn pw-btn-danger pw-btn--sm"><?php esc_html_e( 'Delete', 'postwave' ); ?></button>
            </form>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <?php if ( count( $accounts ) <= 1 ) : ?>
      <div class="pw-empty-state">
        <div class="pw-empty-state__icon">
          <svg viewBox="0 0 24 24"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/><path d="M16 11l2 2 4-4"/></svg>
        </div>
        <p class="pw-empty-state__msg"><?php esc_html_e( 'Only the Primary account is configured. Add a second account to enable routing rules.', 'postwave' ); ?></p>
      </div>
      <?php endif; ?>

      <!-- Add account button -->
      <div style="margin-bottom:20px;">
        <button type="button" class="pw-btn pw-btn-primary" id="pw-add-account-btn">
          + <?php esc_html_e( 'Add account', 'postwave' ); ?>
        </button>
      </div>

      <!-- Add / Edit account inline form -->
      <div class="pw-inline-form pw-hidden" id="pw-account-form">
        <h3 id="pw-account-form-title"><?php esc_html_e( 'Add account', 'postwave' ); ?></h3>
        <form method="post" action="<?php echo $act; ?>">
          <?php wp_nonce_field( 'postwave_save_account' ); ?>
          <input type="hidden" name="action"        value="postwave_save_account">
          <input type="hidden" name="pw_account[id]" id="pw-account-id" value="">

          <div class="pw-form-grid-2">
            <div class="pw-field">
              <label for="pw-account-name"><?php esc_html_e( 'Account name', 'postwave' ); ?></label>
              <input type="text" id="pw-account-name" class="pw-input" name="pw_account[name]" placeholder="<?php esc_attr_e( 'e.g. Primary', 'postwave' ); ?>">
            </div>
            <div class="pw-field">
              <label for="pw-account-server-url"><?php esc_html_e( 'Server URL', 'postwave' ); ?></label>
              <input type="url" id="pw-account-server-url" class="pw-input" name="pw_account[server_url]" placeholder="https://mail.example.com">
            </div>
            <div class="pw-field">
              <label for="pw-account-username"><?php esc_html_e( 'Username', 'postwave' ); ?></label>
              <input type="text" id="pw-account-username" class="pw-input" name="pw_account[username]" autocomplete="off">
            </div>
            <div class="pw-field">
              <label for="pw-account-password"><?php esc_html_e( 'Password', 'postwave' ); ?></label>
              <div class="pw-pass-wrap">
                <input type="password" id="pw-account-password" class="pw-input" name="pw_account[password]"
                  placeholder="<?php esc_attr_e( 'Leave blank to keep current', 'postwave' ); ?>" autocomplete="new-password">
                <button type="button" class="pw-eye-btn" data-for="pw-account-password" tabindex="-1"
                  aria-label="<?php esc_attr_e( 'Toggle password visibility', 'postwave' ); ?>">
                  <svg class="pw-eye-on"  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  <svg class="pw-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
              </div>
            </div>
          </div>

          <details style="margin-top:12px;">
            <summary style="cursor:pointer;font-size:12px;color:var(--pw-muted,#787c82);margin-bottom:8px;"><?php esc_html_e( 'Identity override (advanced)', 'postwave' ); ?></summary>
            <div class="pw-form-grid-2" style="margin-top:8px;">
              <div class="pw-field">
                <label for="pw-account-iid"><?php esc_html_e( 'Identity ID', 'postwave' ); ?></label>
                <input type="text" id="pw-account-iid" class="pw-input" name="pw_account[identity_id]" placeholder="<?php esc_attr_e( 'Leave blank for auto-resolve', 'postwave' ); ?>">
              </div>
              <div class="pw-field">
                <label for="pw-account-iname"><?php esc_html_e( 'Identity name', 'postwave' ); ?></label>
                <input type="text" id="pw-account-iname" class="pw-input" name="pw_account[identity_name]">
              </div>
              <div class="pw-field">
                <label for="pw-account-iemail"><?php esc_html_e( 'Identity email', 'postwave' ); ?></label>
                <input type="email" id="pw-account-iemail" class="pw-input" name="pw_account[identity_email]">
              </div>
            </div>
          </details>

          <div class="pw-form-actions">
            <button type="submit" class="pw-btn pw-btn-primary"><?php esc_html_e( 'Save account', 'postwave' ); ?></button>
            <button type="button" class="pw-btn pw-btn-outline" id="pw-account-form-cancel"><?php esc_html_e( 'Cancel', 'postwave' ); ?></button>
            <button type="button" class="pw-btn pw-btn-secondary" id="pw-test-account-btn"><?php esc_html_e( 'Test connection', 'postwave' ); ?></button>
            <span id="pw-test-account-result" style="font-size:13px;"></span>
          </div>
        </form>
      </div>

      <!-- ══ TAB: ROUTING ══ -->
      <?php elseif ( $tab === 'routing' ) : ?>

      <p style="font-size:13px;color:var(--pw-muted,#787c82);margin-bottom:20px;">
        <?php esc_html_e( 'Routing rules are evaluated in order (first match wins). Each rule sends matching emails via the specified account. Rules are skipped if disabled or if their conditions do not match.', 'postwave' ); ?>
      </p>

      <!-- Rules table -->
      <?php if ( empty( $rules ) ) : ?>
      <div class="pw-empty-state">
        <div class="pw-empty-state__icon">
          <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        </div>
        <p class="pw-empty-state__msg"><?php esc_html_e( 'No routing rules yet. Add a rule to route emails to different accounts based on recipient, subject, or plugin type.', 'postwave' ); ?></p>
      </div>
      <?php endif; ?>
      <?php if ( ! empty( $rules ) ) : ?>
      <div class="pw-panel" style="margin-bottom:20px;">
        <div class="pw-panel-body pw-panel-body-flush">
          <table class="pw-rules-table">
            <thead>
              <tr>
                <th><?php esc_html_e( 'Priority', 'postwave' ); ?></th>
                <th><?php esc_html_e( 'Rule name', 'postwave' ); ?></th>
                <th><?php esc_html_e( 'Conditions', 'postwave' ); ?></th>
                <th><?php esc_html_e( 'Account', 'postwave' ); ?></th>
                <th><?php esc_html_e( 'Enabled', 'postwave' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'postwave' ); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ( $rules as $ri => $rule ) :
                $acct_name = '';
                if ( ! empty( $rule['account_id'] ) ) {
                  $racct     = Postwave_Account_Manager::get( $rule['account_id'] );
                  $acct_name = $racct ? esc_html( $racct['name'] ) : esc_html( $rule['account_id'] );
                }
                $cond_summary = array();
                foreach ( (array) ( isset( $rule['conditions'] ) ? $rule['conditions'] : array() ) as $cond ) {
                  $cond_summary[] = '<code>' . esc_html( $cond['field'] ) . '</code>: ' . esc_html( $cond['value'] );
                }
                $rule_json = esc_attr( wp_json_encode( $rule ) );
              ?>
              <tr>
                <td>
                  <div class="pw-priority-btns">
                    <?php if ( $ri > 0 ) : ?>
                    <form method="post" action="<?php echo $act; ?>" style="display:inline">
                      <?php wp_nonce_field( 'postwave_reorder_rules' ); ?>
                      <input type="hidden" name="action" value="postwave_reorder_rules">
                      <?php
                      $new_order = array_column( $rules, 'id' );
                      $tmp = $new_order[ $ri ]; $new_order[ $ri ] = $new_order[ $ri - 1 ]; $new_order[ $ri - 1 ] = $tmp;
                      foreach ( $new_order as $oid ) :
                      ?>
                      <input type="hidden" name="order[]" value="<?php echo esc_attr( $oid ); ?>">
                      <?php endforeach; ?>
                      <button type="submit" title="<?php esc_attr_e( 'Move up', 'postwave' ); ?>">↑</button>
                    </form>
                    <?php else : ?>
                    <button type="button" disabled>↑</button>
                    <?php endif; ?>
                    <?php if ( $ri < count( $rules ) - 1 ) : ?>
                    <form method="post" action="<?php echo $act; ?>" style="display:inline">
                      <?php wp_nonce_field( 'postwave_reorder_rules' ); ?>
                      <input type="hidden" name="action" value="postwave_reorder_rules">
                      <?php
                      $new_order = array_column( $rules, 'id' );
                      $tmp = $new_order[ $ri ]; $new_order[ $ri ] = $new_order[ $ri + 1 ]; $new_order[ $ri + 1 ] = $tmp;
                      foreach ( $new_order as $oid ) :
                      ?>
                      <input type="hidden" name="order[]" value="<?php echo esc_attr( $oid ); ?>">
                      <?php endforeach; ?>
                      <button type="submit" title="<?php esc_attr_e( 'Move down', 'postwave' ); ?>">↓</button>
                    </form>
                    <?php else : ?>
                    <button type="button" disabled>↓</button>
                    <?php endif; ?>
                  </div>
                </td>
                <td><?php echo esc_html( isset( $rule['name'] ) ? $rule['name'] : '' ); ?></td>
                <td class="pw-rule-conditions">
                  <?php if ( ! empty( $cond_summary ) ) : ?>
                  <?php echo implode( ' <em>' . esc_html( isset( $rule['condition_operator'] ) && 'all' === $rule['condition_operator'] ? __( 'AND', 'postwave' ) : __( 'OR', 'postwave' ) ) . '</em> ', $cond_summary ); ?>
                  <?php else : ?>
                  <em><?php esc_html_e( 'All emails', 'postwave' ); ?></em>
                  <?php endif; ?>
                </td>
                <td><?php echo $acct_name; ?></td>
                <td>
                  <?php if ( ! empty( $rule['enabled'] ) ) : ?>
                  <span class="pw-badge--primary"><?php esc_html_e( 'Yes', 'postwave' ); ?></span>
                  <?php else : ?>
                  <span style="color:var(--pw-muted,#787c82);font-size:12px;"><?php esc_html_e( 'No', 'postwave' ); ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <div style="display:flex;gap:6px;">
                    <button type="button" class="pw-btn pw-btn-secondary pw-btn--sm pw-edit-rule-btn" data-rule="<?php echo $rule_json; ?>">
                      <?php esc_html_e( 'Edit', 'postwave' ); ?>
                    </button>
                    <form method="post" action="<?php echo $act; ?>" style="display:inline;" onsubmit="return confirm('<?php echo esc_js( __( 'Delete this rule?', 'postwave' ) ); ?>')">
                      <?php wp_nonce_field( 'postwave_delete_rule' ); ?>
                      <input type="hidden" name="action"  value="postwave_delete_rule">
                      <input type="hidden" name="rule_id" value="<?php echo esc_attr( $rule['id'] ); ?>">
                      <button type="submit" class="pw-btn pw-btn-danger pw-btn--sm"><?php esc_html_e( 'Delete', 'postwave' ); ?></button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

      <!-- Add rule button -->
      <div style="margin-bottom:20px;">
        <button type="button" class="pw-btn pw-btn-primary" id="pw-add-rule-btn">
          + <?php esc_html_e( 'Add rule', 'postwave' ); ?>
        </button>
      </div>

      <!-- Add / Edit rule inline form -->
      <div class="pw-inline-form pw-hidden" id="pw-rule-form">
        <h3 id="pw-rule-form-title"><?php esc_html_e( 'Add rule', 'postwave' ); ?></h3>
        <form method="post" action="<?php echo $act; ?>">
          <?php wp_nonce_field( 'postwave_save_rule' ); ?>
          <input type="hidden" name="action"       value="postwave_save_rule">
          <input type="hidden" name="pw_rule[id]"   id="pw-rule-id" value="">

          <div class="pw-form-grid-2">
            <div class="pw-field">
              <label for="pw-rule-name"><?php esc_html_e( 'Rule name', 'postwave' ); ?></label>
              <input type="text" id="pw-rule-name" class="pw-input" name="pw_rule[name]" placeholder="<?php esc_attr_e( 'e.g. WooCommerce Orders', 'postwave' ); ?>">
            </div>
            <div class="pw-field" style="display:flex;align-items:center;gap:10px;padding-top:22px;">
              <label class="pw-toggle" style="flex-shrink:0;">
                <input type="checkbox" name="pw_rule[enabled]" id="pw-rule-enabled" value="1" checked class="pw-toggle-cb">
                <span class="pw-toggle-track"><span class="pw-toggle-thumb"></span></span>
              </label>
              <span style="font-size:13px;color:var(--pw-text,#2c3338);"><?php esc_html_e( 'Enabled', 'postwave' ); ?></span>
            </div>
          </div>

          <!-- Condition operator -->
          <div class="pw-field" style="margin-top:16px;">
            <label><?php esc_html_e( 'Match operator', 'postwave' ); ?></label>
            <div style="display:flex;gap:20px;margin-top:6px;">
              <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                <input type="radio" name="pw_rule[condition_operator]" value="any" checked>
                <?php esc_html_e( 'Match ANY condition', 'postwave' ); ?>
              </label>
              <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                <input type="radio" name="pw_rule[condition_operator]" value="all">
                <?php esc_html_e( 'Match ALL conditions', 'postwave' ); ?>
              </label>
            </div>
          </div>

          <!-- Conditions -->
          <div class="pw-field" style="margin-top:16px;">
            <label><?php esc_html_e( 'Conditions', 'postwave' ); ?></label>
            <div class="pw-conditions-list" id="pw-conditions-list" style="margin-top:8px;"></div>
            <button type="button" class="pw-add-condition" id="pw-add-condition-btn">+ <?php esc_html_e( 'Add condition', 'postwave' ); ?></button>
            <p class="pw-desc" style="margin-top:6px;">
              <?php esc_html_e( 'Plugin values: ', 'postwave' ); ?>
              <code>woocommerce</code>, <code>woocommerce:customer_processing_order</code>, <code>gravityforms</code>, <code>fluentform</code>, <code>cf7</code>
            </p>
          </div>

          <!-- Target account -->
          <div class="pw-form-grid-2" style="margin-top:16px;">
            <div class="pw-field">
              <label for="pw-rule-account"><?php esc_html_e( 'Target account', 'postwave' ); ?></label>
              <select id="pw-rule-account" name="pw_rule[account_id]" class="pw-select">
                <?php foreach ( $accounts as $racct ) : ?>
                <option value="<?php echo esc_attr( $racct['id'] ); ?>"><?php echo esc_html( $racct['name'] ); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="pw-field">
              <label for="pw-rule-identity"><?php esc_html_e( 'Override identity ID', 'postwave' ); ?> <em class="pw-label-opt"><?php esc_html_e( 'optional', 'postwave' ); ?></em></label>
              <input type="text" id="pw-rule-identity" class="pw-input" name="pw_rule[identity_id]" placeholder="<?php esc_attr_e( 'Leave blank to use account default', 'postwave' ); ?>">
            </div>
          </div>

          <div class="pw-form-actions">
            <button type="submit" class="pw-btn pw-btn-primary"><?php esc_html_e( 'Save rule', 'postwave' ); ?></button>
            <button type="button" class="pw-btn pw-btn-outline" id="pw-rule-form-cancel"><?php esc_html_e( 'Cancel', 'postwave' ); ?></button>
          </div>
        </form>
      </div>

      <!-- ══ TAB: CONNECTION ══ -->
      <?php elseif ( $tab === 'connection' ) : ?>

      <form method="post" action="<?php echo $act; ?>">
        <?php wp_nonce_field( 'postwave_save' ); ?>
        <input type="hidden" name="action"          value="postwave_save">
        <input type="hidden" name="postwave[_tab]"  value="connection">
        <!-- Preserve general fields -->
        <input type="hidden" name="postwave[enabled]"          value="<?php echo esc_attr( $o( 'enabled', 0 ) ); ?>">
        <input type="hidden" name="postwave[from_name]"        value="<?php echo esc_attr( $o( 'from_name' ) ); ?>">
        <input type="hidden" name="postwave[from_email]"       value="<?php echo esc_attr( $o( 'from_email' ) ); ?>">
        <input type="hidden" name="postwave[test_recipient]"   value="<?php echo esc_attr( $o( 'test_recipient' ) ); ?>">
        <!-- Preserve v1.1 general-tab fields -->
        <input type="hidden" name="postwave[retry_enabled]"    value="<?php echo esc_attr( $o( 'retry_enabled', 0 ) ); ?>">
        <input type="hidden" name="postwave[retry_max]"        value="<?php echo esc_attr( $o( 'retry_max', 3 ) ); ?>">
        <input type="hidden" name="postwave[retry_delay]"      value="<?php echo esc_attr( $o( 'retry_delay', 300 ) ); ?>">
        <input type="hidden" name="postwave[tracking_enabled]" value="<?php echo esc_attr( $o( 'tracking_enabled', 0 ) ); ?>">

        <div class="pw-panel">
          <div class="pw-panel-header">
            <h3><?php esc_html_e( 'Server Configuration', 'postwave' ); ?></h3>
            <p><?php esc_html_e( 'Your JMAP server URL, username, and password.', 'postwave' ); ?></p>
          </div>
          <div class="pw-panel-body">
            <div class="pw-field">
              <label for="pw-server-url"><?php esc_html_e( 'JMAP Server URL', 'postwave' ); ?></label>
              <input type="url" id="pw-server-url" class="pw-input" name="postwave[server_url]"
                value="<?php echo esc_attr( $o( 'server_url' ) ); ?>"
                placeholder="https://mail.example.com">
              <span class="pw-field-hint"><?php esc_html_e( 'Session is auto-discovered at /.well-known/jmap', 'postwave' ); ?></span>
            </div>
            <div class="pw-row-2">
              <div class="pw-field">
                <label for="pw-username"><?php esc_html_e( 'Username', 'postwave' ); ?></label>
                <input type="text" id="pw-username" class="pw-input" name="postwave[username]"
                  value="<?php echo esc_attr( $o( 'username' ) ); ?>" autocomplete="off">
              </div>
              <div class="pw-field">
                <label for="pw-password"><?php esc_html_e( 'Password', 'postwave' ); ?></label>
                <div class="pw-pass-wrap">
                  <input type="password" id="pw-password" class="pw-input" name="postwave[password]"
                    placeholder="<?php esc_attr_e( 'Leave blank to keep current', 'postwave' ); ?>"
                    autocomplete="new-password">
                  <button type="button" class="pw-eye-btn" data-for="pw-password" tabindex="-1"
                    aria-label="<?php esc_attr_e( 'Toggle password visibility', 'postwave' ); ?>">
                    <svg class="pw-eye-on"  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="pw-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                  </button>
                </div>
                <?php if ( ! empty( $options['password'] ) ) : ?>
                <span class="pw-field-hint pw-field-hint-ok">
                  <svg viewBox="0 0 20 20" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                  <?php esc_html_e( 'Password saved — leave blank to keep current', 'postwave' ); ?>
                </span>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <div class="pw-panel-footer">
            <button type="submit" class="pw-btn pw-btn-primary"><?php esc_html_e( 'Save credentials', 'postwave' ); ?></button>
          </div>
        </div>

        <!-- Sender Identity panel -->
        <div class="pw-panel">
          <div class="pw-panel-header">
            <h3><?php esc_html_e( 'Sender Identity', 'postwave' ); ?></h3>
            <p><?php esc_html_e( 'Choose which JMAP sending identity to use. Auto-resolve matches the From email to an identity on your server.', 'postwave' ); ?></p>
          </div>
          <div class="pw-panel-body">
            <div class="pw-field">
              <label class="pw-label" for="pw-identity-select"><?php esc_html_e( 'Identity', 'postwave' ); ?></label>
              <div class="pw-identity-wrap">
                <div class="pw-identity-controls">
                  <select id="pw-identity-select" name="postwave[identity_id]" class="pw-select pw-identity-dropdown">
                    <option value=""><?php esc_html_e( '— Auto-resolve (recommended) —', 'postwave' ); ?></option>
                    <?php
                    $saved_id    = $options['identity_id'] ?? '';
                    if ( ! empty( $saved_id ) ) :
                      $saved_name  = sanitize_text_field( $options['identity_name'] ?? $saved_id );
                      $saved_email = sanitize_email( $options['identity_email'] ?? '' );
                      echo '<option value="' . esc_attr( $saved_id ) . '" selected>' . esc_html( $saved_name . ( $saved_email ? ' <' . $saved_email . '>' : '' ) ) . '</option>';
                    endif;
                    ?>
                  </select>
                  <!-- Hidden fields so the selected identity name/email survive the save round-trip -->
                  <input type="hidden" id="pw-identity-name"  name="postwave[identity_name]"  value="<?php echo esc_attr( $options['identity_name'] ?? '' ); ?>">
                  <input type="hidden" id="pw-identity-email" name="postwave[identity_email]" value="<?php echo esc_attr( $options['identity_email'] ?? '' ); ?>">
                  <button type="button" id="pw-load-identities" class="pw-btn pw-btn--secondary pw-btn-secondary">
                    <?php esc_html_e( 'Load identities', 'postwave' ); ?>
                  </button>
                </div>
                <p class="pw-desc" style="margin-top:6px;"><?php esc_html_e( 'Click "Load identities" to fetch the list from your JMAP server. Save credentials on the Connection tab first.', 'postwave' ); ?></p>
                <p id="pw-identity-status" class="pw-desc pw-hidden" style="margin-top:4px;"></p>
              </div>
            </div>
          </div>
          <div class="pw-panel-footer">
            <button type="submit" class="pw-btn pw-btn-primary"><?php esc_html_e( 'Save identity', 'postwave' ); ?></button>
          </div>
        </div>
      </form>

      <!-- Test connection panel (AJAX — outside form) -->
      <div class="pw-panel pw-panel-test">
        <div class="pw-panel-header">
          <h3><?php esc_html_e( 'Test Connection', 'postwave' ); ?></h3>
          <p><?php esc_html_e( 'Save your credentials first, then verify the server responds correctly.', 'postwave' ); ?></p>
        </div>
        <div class="pw-panel-body">
          <div class="pw-test-actions">
            <button type="button" class="pw-btn pw-btn-secondary" id="pw-test-conn">
              <svg viewBox="0 0 20 20" fill="currentColor" width="15" height="15"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
              <?php esc_html_e( 'Test connection', 'postwave' ); ?>
            </button>
            <button type="button" class="pw-btn pw-btn-secondary" id="pw-test-email">
              <svg viewBox="0 0 20 20" fill="currentColor" width="15" height="15"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>
              <?php esc_html_e( 'Send test email', 'postwave' ); ?>
            </button>
          </div>

          <div id="pw-steps" style="display:none">
            <div class="pw-step-row" id="pw-step-discover">
              <span class="pw-step-dot">○</span>
              <span><?php esc_html_e( 'Discovering JMAP session…', 'postwave' ); ?></span>
            </div>
            <div class="pw-step-row pw-step-row--pending" id="pw-step-identity">
              <span class="pw-step-dot">○</span>
              <span><?php esc_html_e( 'Resolving sender identity…', 'postwave' ); ?></span>
            </div>
            <div class="pw-step-row pw-step-row--pending" id="pw-step-done">
              <span class="pw-step-dot">○</span>
              <span><?php esc_html_e( 'Verifying capabilities…', 'postwave' ); ?></span>
            </div>
          </div>

          <div id="pw-test-result" style="display:none"></div>
        </div>
      </div>

      <!-- ══ TAB: LOG ══ -->
      <?php else : ?>

      <div class="pw-panel">
        <div class="pw-panel-header pw-panel-header-row">
          <div>
            <h3><?php esc_html_e( 'Mail Log', 'postwave' ); ?></h3>
            <p><?php esc_html_e( 'Last 100 send attempts. Bodies are never stored.', 'postwave' ); ?></p>
          </div>
          <?php if ( ! empty( $entries ) ) : ?>
          <div style="display:flex;gap:8px;align-items:center;flex-shrink:0;">
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
              <input type="hidden" name="action" value="postwave_export_log">
              <?php wp_nonce_field( 'postwave_export_log' ); ?>
              <button type="submit" class="pw-btn pw-btn-secondary pw-btn--sm">
                &#8595; <?php esc_html_e( 'Export CSV', 'postwave' ); ?>
              </button>
            </form>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
              <input type="hidden" name="action" value="postwave_clear_log">
              <?php wp_nonce_field( 'postwave_clear_log' ); ?>
              <button type="submit" class="pw-btn pw-btn-danger pw-btn--sm"
                onclick="return confirm('<?php echo esc_js( __( 'Clear all log entries? This cannot be undone.', 'postwave' ) ); ?>')">
                <svg viewBox="0 0 20 20" fill="currentColor" width="13" height="13"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <?php esc_html_e( 'Clear log', 'postwave' ); ?>
              </button>
            </form>
          </div>
          <?php endif; ?>
        </div>
        <div class="pw-panel-body pw-panel-body-flush">

          <?php if ( empty( $entries ) ) : ?>
          <div class="pw-empty-state">
            <div class="pw-empty-icon">
              <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="6" y="10" width="36" height="28" rx="3"/><polyline points="6,16 24,26 42,16"/></svg>
            </div>
            <h4><?php esc_html_e( 'No emails logged yet', 'postwave' ); ?></h4>
            <p><?php esc_html_e( 'Sent and failed emails will appear here once Postwave is active.', 'postwave' ); ?></p>
          </div>
          <?php else : ?>
          <div class="pw-table-scroll">
            <table class="pw-table">
              <thead>
                <tr>
                  <th><?php esc_html_e( 'Status', 'postwave' ); ?></th>
                  <th><?php esc_html_e( 'Date / Time', 'postwave' ); ?></th>
                  <th><?php esc_html_e( 'To', 'postwave' ); ?></th>
                  <th><?php esc_html_e( 'Subject', 'postwave' ); ?></th>
                  <th><?php esc_html_e( 'Details', 'postwave' ); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ( $entries as $e ) :
                  $sent         = ( $e['status'] ?? '' ) === 'sent';
                  $ts           = (int) ( $e['timestamp'] ?? 0 );
                  $retry_status = $e['retry_status'] ?? '';
                  $opened_at    = $e['opened_at'] ?? null;
                ?>
                <tr>
                  <td>
                    <span class="pw-badge-pill pw-badge-pill-<?php echo $sent ? 'success' : 'danger'; ?>">
                      <?php echo $sent ? esc_html__( 'Sent', 'postwave' ) : esc_html__( 'Failed', 'postwave' ); ?>
                    </span>
                    <?php if ( 'retried' === $retry_status ) : ?>
                    <span class="pw-badge pw-badge--retried" title="<?php esc_attr_e( 'Sent via retry queue', 'postwave' ); ?>">
                      <?php echo esc_html( sprintf(
                        /* translators: %d: number of retries */
                        _n( 'retry %d', 'retry %d', (int) ( $e['retry_count'] ?? 1 ), 'postwave' ),
                        (int) ( $e['retry_count'] ?? 1 )
                      ) ); ?>
                    </span>
                    <?php elseif ( 'exhausted' === $retry_status ) : ?>
                    <span class="pw-badge pw-badge--exhausted" title="<?php esc_attr_e( 'All retry attempts exhausted', 'postwave' ); ?>">
                      <?php esc_html_e( 'exhausted', 'postwave' ); ?>
                    </span>
                    <?php endif; ?>
                    <?php if ( ! empty( $opened_at ) ) : ?>
                    <span class="pw-badge pw-badge--opened" title="<?php echo esc_attr( sprintf(
                      /* translators: %s: date/time */
                      __( 'Opened at %s', 'postwave' ),
                      wp_date( 'Y-m-d H:i', (int) $opened_at )
                    ) ); ?>">
                      <?php esc_html_e( 'opened', 'postwave' ); ?>
                    </span>
                    <?php endif; ?>
                  </td>
                  <td class="pw-td-mono"><?php echo esc_html( $ts ? wp_date( 'Y-m-d H:i', $ts ) : '—' ); ?></td>
                  <td class="pw-td-truncate"><?php echo esc_html( $e['to'] ?? '' ); ?></td>
                  <td class="pw-td-truncate"><?php echo esc_html( $e['subject'] ?? '' ); ?></td>
                  <td>
                    <button type="button" class="pw-detail-btn"><?php esc_html_e( 'Details', 'postwave' ); ?> ▾</button>
                    <dl class="pw-detail-panel" style="display:none">
                      <?php if ( ! empty( $e['from'] ) ) : ?>
                      <div><dt><?php esc_html_e( 'From', 'postwave' ); ?></dt><dd><code><?php echo esc_html( $e['from'] ); ?></code></dd></div>
                      <?php endif; ?>
                      <?php if ( ! empty( $e['account_id'] ) ) : ?>
                      <div><dt><?php esc_html_e( 'Account', 'postwave' ); ?></dt><dd><code><?php echo esc_html( $e['account_id'] ); ?></code></dd></div>
                      <?php endif; ?>
                      <?php if ( ! empty( $e['identity_id'] ) ) : ?>
                      <div><dt><?php esc_html_e( 'Identity', 'postwave' ); ?></dt><dd><code><?php echo esc_html( $e['identity_id'] ); ?></code></dd></div>
                      <?php endif; ?>
                      <?php if ( ! empty( $e['email_id'] ) ) : ?>
                      <div><dt><?php esc_html_e( 'Email ID', 'postwave' ); ?></dt><dd><code><?php echo esc_html( $e['email_id'] ); ?></code></dd></div>
                      <?php endif; ?>
                      <?php if ( ! empty( $opened_at ) ) : ?>
                      <div><dt><?php esc_html_e( 'Opened', 'postwave' ); ?></dt><dd><?php echo esc_html( wp_date( 'Y-m-d H:i:s', (int) $opened_at ) ); ?></dd></div>
                      <?php endif; ?>
                      <?php if ( ! empty( $e['error'] ) ) : ?>
                      <div class="pw-detail-error"><dt><?php esc_html_e( 'Error', 'postwave' ); ?></dt><dd><?php echo esc_html( $e['error'] ); ?></dd></div>
                      <?php endif; ?>
                    </dl>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>

        </div>
      </div>

      <?php endif; /* tab switch */ ?>

    </main><!-- /.pw-main -->
  </div><!-- /.pw-app-body -->
</div><!-- #pw-page -->

<?php endif; /* wizard / dashboard */ ?>
