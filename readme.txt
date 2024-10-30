=== Search Log ===
Contributors: thrica
Donate link: http://cameronharwick.com/
Tags: search
Requires at least: 4.0
Tested up to: 4.5.1
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Keeps a log of what people search for on your site.

== Description ==

Pretty simple. Every time someone searches a term on your website, this plugin tracks it.

It also tells you (roughly) where they're searching from. Note that the plugin does **not** log searches from logged in users, so you can search your own site without polluting the logs.

*Quis scrutatur ipsos scrutatores?*

== Installation ==

Just activate and forget.

If you don't see the admin widget on the admin dashboard, click on 'Screen Options' at the top of the page and make sure 'Recent Searches' is checked.

== Frequently Asked Questions ==

= Do front-end users have any indication that searches are being logged? =

Nope.

= The flags don't show up sometimes =

Geolocation is very far from perfect. The service used by this plugin polls several servers, and if they disagree on the location, it doesn't return anything. You can still click on the IP address to see the location results from those various services.

The service also limits you to 10,000 queries per hour. If your blog is extremely high-volume, or if your server shares an IP address with a high-volume site, it's possible that you could run into this limit.

== Screenshots ==

1. The admin widget.

== Changelog ==

= 1.0.1 =
* Fixed a bug that broke the admin widget when geolocation service had been unavailable

= 1.0 =
* AJAXed dashboard widget, supports paging through results
* Linked search terms to search page
* Ability to delete searches
 
= 0.9 =
* Geolocation
* Fixed layout bugs

= 0.8 =
* Initial release