=== SkillScore Ebook Commerce ===
Contributors: SkillScore IT Solutions and Training
Developer: Tijani Bulama
Tags: ebook, digital downloads, payment gateway, commerce, elementor
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A comprehensive ebook commerce solution with audio previews, multiple payment gateways, and secure downloads.

== Description ==

SkillScore Ebook Commerce is a powerful WordPress plugin that enables you to sell ebooks directly from your WordPress site. With support for multiple payment gateways, audio previews, and secure file delivery, it's the complete solution for digital book sales.

= Features =

* **Custom Post Type for Ebooks** - Manage your ebook library with a dedicated post type
* **Multiple Payment Gateways** - Accept payments via Paystack, Flutterwave, Stripe, and PayPal
* **Audio Previews** - Offer voice samples of your ebooks using TTS or uploaded audio
* **Secure Downloads** - Protected file delivery with expiring links and download limits
* **Shortcodes** - Display ebooks anywhere with simple shortcodes
* **Elementor Integration** - Drag-and-drop ebook widgets for Elementor
* **Tailwind CSS Styling** - Modern, responsive design out of the box
* **Stock Management** - Track inventory or offer unlimited digital copies
* **Sales Reports** - Monitor orders and revenue from your dashboard
* **Download Logs** - Track who downloaded what and when

= Payment Gateways =

* Paystack - Perfect for Nigerian and African markets
* Flutterwave - Pan-African payment solution
* Stripe - Global payment processing
* PayPal - Worldwide payment acceptance

= Shortcodes =

Display all ebooks:
`[skillscore_ebooks limit="12" category="fiction" columns="3"]`

Display single ebook:
`[skillscore_ebook id="123"]`

= Elementor Widget =

The plugin includes a custom Elementor widget with full control over:
* Display type (grid or single)
* Number of ebooks to show
* Category filtering
* Column layout
* Sorting options

== Installation ==

1. Upload the `skillscore-ebook-commerce` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Ebooks > Settings to configure payment gateways
4. Create your first ebook under Ebooks > Add New
5. Use shortcodes or Elementor widget to display ebooks

== Frequently Asked Questions ==

= How do I add an ebook? =

Go to Ebooks > Add New in your WordPress admin. Fill in the details, upload your ebook file (PDF, EPUB, or DOCX), set a price, and publish.

= Which file formats are supported? =

The plugin supports PDF, EPUB, and DOCX ebook files up to 50MB.

= How do I set up payment gateways? =

Go to Ebooks > Settings > Payment Gateways. Enable your desired gateway and enter your API keys. You can enable multiple gateways and customers will choose their preferred method.

= Can I offer audio previews? =

Yes! You can either upload a global voice sample that applies to all ebooks, or configure a TTS (Text-to-Speech) engine like Piper TTS or Coqui TTS to generate previews automatically.

= How does download security work? =

After successful payment, customers receive a unique download link with:
* Expiration date (configurable, default 30 days)
* Download limit (configurable, default 5 downloads)
* One-time use tokens
* IP and user agent logging

= Can I revoke download access? =

Yes, from the Downloads admin page, you can revoke any download link at any time.

= Is the plugin compatible with Gutenberg? =

Yes, the shortcodes work perfectly in Gutenberg blocks.

= Does it work with Elementor? =

Yes, the plugin includes a native Elementor widget for easy drag-and-drop integration.

== Screenshots ==

1. Ebook grid display with Tailwind CSS styling
2. Single ebook view with purchase form
3. Admin ebook editor with meta boxes
4. Payment gateway settings
5. Orders management page
6. Downloads tracking page
7. Elementor widget settings

== Changelog ==

= 1.0.0 =
* Initial release
* Custom post type for ebooks
* Paystack, Flutterwave, Stripe, and PayPal integration
* Audio preview system
* Secure download handler
* Shortcodes support
* Elementor widget
* Tailwind CSS styling
* Admin settings and reports

== Upgrade Notice ==

= 1.0.0 =
Initial release of SkillScore Ebook Commerce.

== Configuration ==

= Payment Gateways =

**Paystack:**
1. Get API keys from https://dashboard.paystack.com/#/settings/developer
2. Enter Public Key and Secret Key in settings
3. Enable Paystack

**Flutterwave:**
1. Get API keys from https://dashboard.flutterwave.com/settings/apis
2. Enter Public Key and Secret Key in settings
3. Enable Flutterwave

**Stripe:**
1. Get API keys from https://dashboard.stripe.com/apikeys
2. Enter Publishable Key and Secret Key in settings
3. Enable Stripe

**PayPal:**
1. Create app at https://developer.paypal.com/developer/applications
2. Get Client ID and Secret
3. Choose Sandbox or Live mode
4. Enable PayPal

= Audio Preview =

**Option 1: Global Voice Sample**
1. Go to Ebooks > Settings > Voice Preview
2. Enable "Use Global Voice Sample"
3. Upload an MP3/WAV/OGG audio file
4. This sample will be used for all ebooks

**Option 2: TTS Engine (Advanced)**
1. Install Piper TTS or Coqui TTS on your server
2. Configure the executable path in settings
3. Audio will be generated automatically from ebook excerpts

== Developer Notes ==

= Hooks & Filters =

The plugin provides various hooks for customization:

`skillscore_ebook_before_purchase` - Before payment initiation
`skillscore_ebook_after_purchase` - After successful payment
`skillscore_ebook_download_started` - When download begins
`skillscore_ebook_payment_failed` - When payment fails

= Template Override =

You can override templates by copying them to your theme:
`your-theme/skillscore-ebook/ebook-card.php`
`your-theme/skillscore-ebook/ebook-single.php`

== Support ==

For support, feature requests, or bug reports, please contact:
SkillScore IT Solutions and Training
Developer: Tijani Bulama

== License ==

This plugin is licensed under the GPL v2 or later.
