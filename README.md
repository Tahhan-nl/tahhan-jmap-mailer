<div align="center">

<img src="https://raw.githubusercontent.com/Tahhan-nl/postwave-jmap/main/assets/img/banner-1544x500.png" alt="Postwave Banner" width="100%">

# Postwave

### Modern JMAP Mail for WordPress

**Send every WordPress email through a real mail server — no SMTP ports, no relay limits, no deliverability headaches.**  
Postwave replaces WordPress's built-in mailer with the modern [JMAP protocol](https://jmap.io/) (RFC 8620 / RFC 8621).

---

[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue?logo=wordpress&logoColor=white)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php&logoColor=white)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%202.0-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.3.0-6366f1)](https://github.com/Tahhan-nl/postwave-jmap/releases)
[![Tested up to](https://img.shields.io/badge/Tested%20up%20to-WP%206.7-success)](https://wordpress.org/)

</div>

---

## What is Postwave?

Most WordPress sites send email through SMTP — a protocol designed in 1982. It requires open ports, relay credentials, and third-party services that rate-limit you or charge per email.

**Postwave** takes a different approach: it uses **JMAP** (JSON Meta Application Protocol), the modern RFC-standard replacement for IMAP/SMTP. JMAP talks over standard HTTPS, works through firewalls, requires no port configuration, and is supported by modern mail servers like [Stalwart Mail Server](https://stalw.art), [Fastmail](https://fastmail.com), and [Cyrus IMAP](https://www.cyrusimap.org/).

> **TL;DR** — Install Postwave, enter your JMAP server URL + credentials, and every `wp_mail()` call on your site is delivered through your own mail infrastructure.

---

## Features

| | Feature | Description |
|---|---|---|
| ⚡ | **JMAP Protocol** | Full RFC 8620 / RFC 8621 implementation — `Email/set` + `EmailSubmission/set` two-step delivery |
| 🔍 | **Auto-discovery** | Session discovered automatically from `/.well-known/jmap` — no manual API URL needed |
| 🔒 | **Secure credentials** | HTTP Basic auth over HTTPS — credentials never leave your server |
| 📎 | **Full attachment support** | Uploads blobs to JMAP upload endpoint, supports any file type |
| 🌐 | **HTML + plain text** | Sends `multipart/alternative` with auto-generated plain-text fallback |
| 📋 | **Mail log** | Tracks every send attempt — recipient, subject, status, JMAP IDs — bodies never stored |
| 🧩 | **WordPress-native** | Uses `wp_remote_get/post`, `WP_Error`, hooks, sanitization — zero external dependencies |
| 🔄 | **Reverse-proxy aware** | Normalises internal URLs from JMAP sessions behind reverse proxies |
| 🎨 | **Professional admin UI** | Clean dashboard with live connection testing, setup wizard, and real-time stats |

---

## Screenshots

| General Settings | Connection Testing | Mail Log |
|---|---|---|
| Toggle enable/disable, configure sender name & email | Live JMAP session test with step-by-step feedback | Expandable log entries with JMAP IDs and error details |

---

## Requirements

- **WordPress** 5.8 or higher
- **PHP** 7.4 or higher
- A **JMAP-capable mail server** (see [Compatible Servers](#compatible-servers))

---

## Installation

### From WordPress Admin (recommended)
1. Download the [latest release](https://github.com/Tahhan-nl/postwave-jmap/releases) ZIP
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload the ZIP and click **Install Now**
4. Click **Activate**

### Manually via FTP
1. Download and unzip the release
2. Upload the `postwave/` folder to `/wp-content/plugins/`
3. Go to **Plugins** in WordPress admin and activate **Postwave**

### Via WP-CLI
```bash
wp plugin install https://github.com/Tahhan-nl/postwave-jmap/releases/latest/download/postwave.zip --activate
```

---

## Configuration

After activation, Postwave shows a **setup wizard** if no server is configured yet.

### Step 1 — Server
Enter your JMAP server base URL (e.g. `https://mail.example.com`).  
The session document is auto-discovered at `/.well-known/jmap`.  
Enter your username and password.

### Step 2 — Sender
Set the **From Name** and **From Email** address for outgoing mail.  
Optionally add a **Test Recipient** for the "Send Test Email" button.

### Step 3 — Activate
Toggle **Enable Postwave** to start routing all WordPress mail through JMAP.

### Testing the connection
Go to **Postwave → Connection** and click **Test connection**.  
Postwave will:
1. Discover the JMAP session
2. Resolve the sender identity
3. Verify server capabilities

Click **Send test email** to fire a real email through the full send pipeline.

---

## Compatible Servers

| Server | JMAP Support | Notes |
|---|---|---|
| [Stalwart Mail Server](https://stalw.art) | ✅ Full | Recommended — open source, self-hosted |
| [Fastmail](https://fastmail.com) | ✅ Full | Commercial, hosted |
| [Cyrus IMAP](https://www.cyrusimap.org/) | ✅ Full | Enterprise self-hosted |
| [Apache James](https://james.apache.org/) | ✅ Full | Open source, Java-based |
| [Dovecot](https://www.dovecot.org/) | ⚠️ Partial | Requires JMAP plugin |
| Gmail / Outlook | ❌ No | Use their SMTP bridges instead |

---

## How It Works

```
WordPress calls wp_mail()
        │
        ▼
Postwave hooks pre_wp_mail
        │
        ├─ Discovers JMAP session (/.well-known/jmap)
        ├─ Resolves sender identity (Identity/get)
        ├─ Uploads attachments as blobs (upload endpoint)
        ├─ Creates email object (Email/set)
        └─ Submits for delivery (EmailSubmission/set)
                │
                ▼
        Mail delivered ✓  →  logged as "sent"
        Error           →  logged as "failed" + wp_mail_failed fired
```

---

## Roadmap

### ✅ Version 1.0 — Foundation
- [x] Full JMAP RFC 8620 / 8621 implementation
- [x] `/.well-known/jmap` auto-discovery with redirect following
- [x] HTTP Basic authentication
- [x] `Email/set` + `EmailSubmission/set` two-step delivery
- [x] HTML + plain-text multipart support
- [x] File attachment support via blob upload
- [x] CC, BCC, Reply-To header parsing
- [x] Reverse-proxy URL normalisation
- [x] Mail log (last 100 entries, no message bodies stored)
- [x] Professional admin UI with setup wizard
- [x] Live connection testing (AJAX)
- [x] PHP 7.4 compatibility
- [x] WordPress.org submission assets

---

### ✅ Version 1.1 — Reliability
- [x] **Retry queue** — failed sends automatically retried via WP-Cron
- [x] **Multiple from identities** — per-form or per-plugin sender overrides
- [x] **Log export** — download mail log as CSV
- [x] **Email open tracking** — optional pixel tracking with privacy controls

---

### ✅ Version 1.2 — Multi-account
- [x] **Multiple JMAP accounts** — route different email types to different servers
- [x] **Routing rules** — send password resets from account A, newsletters from account B
- [x] **WooCommerce integration** — dedicated routing for order emails
- [x] **Fluent Forms / Gravity Forms integration** — per-form sender identity

---

### ✅ Version 1.3 — Polish *(current)*
- [x] **Setup wizard overlay** — guided first-time configuration
- [x] **Status bar** — send statistics on the General tab
- [x] **Empty states** — Accounts, Routing, and Mail Log tabs
- [x] **Toast notifications** — save action feedback
- [x] **uninstall.php** — clean plugin removal
- [x] **UI polish** — accessibility and documentation updates

---

### 🚀 Version 1.4 — Advanced routing
- [ ] **Delivery status webhooks** — listen for JMAP push notifications
- [ ] **Bounce handling** — parse delivery failure reports, mark addresses as bounced
- [ ] **Fluent Forms / Gravity Forms** — per-form sender identity

---

### 🔮 Version 2.0 — Platform
- [ ] **OAuth 2.0 / XOAUTH2** — passwordless authentication for supported servers
- [ ] **Transactional email templates** — beautiful HTML email templates built in
- [ ] **Suppression list** — never send to bounced or unsubscribed addresses

---

## Privacy

Postwave stores the following per email attempt in `wp_options`:
- Timestamp
- Recipient address(es)
- Subject line
- Send status (sent / failed)
- JMAP account ID, identity ID, email object ID
- Error message (if failed)

**Message bodies, CC/BCC addresses, and attachment contents are never stored.**  
The log is capped at 100 entries. You can clear it at any time from **Postwave → Mail Log**.

---

## Developer Notes

### Hooks

```php
// Fires after every successful send
do_action( 'wp_mail_sent', $to, $subject, $message, $headers, $attachments );

// Fires on failure (WordPress core hook)
do_action( 'wp_mail_failed', $wp_error );
```

### Filters

```php
// Modify JMAP request options before sending
add_filter( 'postwave_request_args', function( $args, $method ) {
    return $args;
}, 10, 2 );
```

### Bypass Postwave for a single send

```php
remove_filter( 'pre_wp_mail', [ Postwave_Mailer::class, 'send' ], 10 );
wp_mail( $to, $subject, $message );
```

---

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/my-feature`
3. Commit your changes with clear commit messages
4. Open a Pull Request

Please follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/).

---

## Security

Found a vulnerability? Please report it privately via [GitHub Security Advisories](https://github.com/Tahhan-nl/postwave-jmap/security/advisories/new) — do **not** open a public issue.

---

## License

Postwave is open-source software licensed under the [GNU General Public License v2.0](LICENSE).

```
Postwave — JMAP Mail for WordPress
Copyright (C) 2026 Tahhan.nl

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.
```

---

<div align="center">

Built with care by [Tahhan.nl](https://tahhan.nl)

</div>
