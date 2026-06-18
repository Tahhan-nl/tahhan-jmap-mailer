=== Postwave JMAP ===
Contributors: tahhan
Tags: email, mail, jmap, smtp, transactional-email
Requires at least: 5.8
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send WordPress emails via JMAP — the modern RFC 8620/8621 mail protocol. No SMTP ports. Works with Stalwart, Fastmail, Cyrus and more.

== Description ==

**Postwave JMAP** replaces WordPress's default mailer with [JMAP](https://jmap.io/) — the modern JSON-based protocol that supersedes SMTP and IMAP (RFC 8620 / RFC 8621).

Most WordPress sites send email through SMTP — a protocol designed in 1982 that requires open firewall ports, relay credentials, and third-party services that rate-limit or charge per message.

Postwave takes a different approach. JMAP runs over standard HTTPS, needs no special ports, works behind firewalls, and is natively supported by modern mail servers.

= How it works =

1. Postwave hooks into `wp_mail()` via the `pre_wp_mail` filter
2. Auto-discovers your JMAP session from `/.well-known/jmap`
3. Resolves your sender identity via `Identity/get`
4. Uploads any attachments as blobs
5. Creates the email via `Email/set`
6. Submits it for delivery via `EmailSubmission/set`

Every step is logged with status, recipient, subject, and JMAP IDs — message bodies are never stored.

= Features =

* **JMAP Protocol** — full RFC 8620 / RFC 8621 implementation
* **Auto-discovery** — session found automatically at `/.well-known/jmap`
* **Attachments** — any file type, uploaded via JMAP blob endpoint
* **HTML mail** — multipart/alternative with auto plain-text fallback
* **CC, BCC, Reply-To** — full header parsing
* **Mail log** — last 100 attempts, bodies never stored, clearable at any time
* **Live connection test** — verify JMAP session, identity, and capabilities without sending
* **Test email** — fire a real email through the full pipeline with one click
* **Setup wizard** — guided 3-step setup for new installs
* **Reverse-proxy support** — normalises internal URLs in JMAP sessions
* **No external dependencies** — uses WordPress's own `wp_remote_get/post`
* **PHP 7.4+** compatible

= Compatible mail servers =

* [Stalwart Mail Server](https://stalw.art) — recommended (open source, self-hosted)
* [Fastmail](https://fastmail.com)
* [Cyrus IMAP](https://www.cyrusimap.org/)
* [Apache James](https://james.apache.org/)
* Any RFC 8620 / 8621 compliant server

= Privacy =

Postwave stores per send attempt: timestamp, recipient address, subject, status, JMAP object IDs, and error message if failed. **Message bodies, attachment contents, and passwords are never stored in the log.**

== Installation ==

= Automatic installation =

1. Go to **Plugins → Add New** in your WordPress admin
2. Search for **Postwave JMAP**
3. Click **Install Now**, then **Activate**

= Manual installation =

1. Download the plugin ZIP from the [releases page](https://github.com/Tahhan-nl/postwave/releases)
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload the ZIP and activate

= After activation =

1. Go to **Postwave JMAP** in your WordPress admin menu
2. The setup wizard will guide you through configuration
3. Enter your JMAP server URL, username, and password
4. Set your From Name and From Email
5. Enable the plugin and test the connection

== Frequently Asked Questions ==

= What is JMAP? =

JMAP (JSON Meta Application Protocol) is a modern open standard (RFC 8620) that replaces SMTP and IMAP. It runs over HTTPS, requires no special firewall ports, and is faster and more reliable than SMTP for sending transactional mail.

= Do I need a special mail server? =

Yes — your mail server must support JMAP (RFC 8620/8621). We recommend [Stalwart Mail Server](https://stalw.art), which is free, open source, and easy to self-host. Fastmail and Cyrus IMAP also fully support JMAP.

= Does this work with Gmail or Outlook? =

No. Gmail and Outlook do not support the JMAP protocol. Use their SMTP credentials with a standard SMTP plugin instead.

= Where is the plugin settings page? =

After activation, **Postwave JMAP** appears as its own item in the WordPress admin sidebar (not under Settings).

= Is my password stored securely? =

Your password is stored in `wp_options` using WordPress's standard option storage. It is never written to log files or transmitted anywhere other than to your configured JMAP server over HTTPS.

= Why is Postwave better than SMTP plugins? =

SMTP requires an open port (25, 465, or 587), which many hosting providers block. JMAP uses standard HTTPS (port 443) which is always available. JMAP is also stateless, supports batch requests, and has a cleaner API.

= Can I see which emails were sent? =

Yes — the **Mail Log** tab shows the last 100 send attempts including status, recipient, subject, timestamp, and JMAP IDs. Message bodies are never stored.

= What happens if a send fails? =

Postwave logs the failure with the error message and fires WordPress's standard `wp_mail_failed` action, so other plugins can react to it.

= Will this slow down my site? =

Postwave only makes an HTTP request when an email is actually being sent. It does not add any overhead to regular page loads.

== Screenshots ==

1. **General tab** — toggle Postwave on/off and configure sender name and email address
2. **Connection tab** — enter your JMAP server URL and credentials
3. **Live connection test** — verify the session, sender identity, and server capabilities
4. **Mail Log** — expandable entries showing status, JMAP IDs, and error details
5. **Setup wizard** — guided 3-step onboarding for new installs

== Changelog ==

= 1.0.0 =
* Initial release
* Full JMAP RFC 8620 / RFC 8621 implementation
* Auto-discovery of JMAP session at `/.well-known/jmap`
* Two-step delivery: `Email/set` + `EmailSubmission/set`
* HTML + plain-text multipart support
* File attachment support via JMAP blob upload
* CC, BCC, Reply-To header parsing
* Reverse-proxy URL normalisation
* Mail log (last 100 entries, message bodies never stored)
* Professional admin UI with setup wizard and live connection testing
* PHP 7.4+ compatible, WordPress 5.8+

== Upgrade Notice ==

= 1.0.0 =
Initial release — no upgrade steps required.
