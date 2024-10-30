=== Login IP & Country Restriction ===
Contributors: Iulia Cazan
Tags: country restriction, login restriction, block country, block IP, country firewall
Requires at least: 5.1
Tested up to: 6.7
Stable tag: 6.6.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate Link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=JJA37EHZXWUTJ

Tighten your website security and fight against dictionary bot attacks originating from other countries, by denying access.


== Description ==
This plugin hooks in the authenticate filter. By default, the plugin is set to allow all access and you can configure the plugin to allow the login only from some specified IPs or the specified countries. PLEASE MAKE SURE THAT YOU CONFIGURE THE PLUGIN TO ALLOW YOUR OWN ACCESS. If you set a restriction by IP, then you have to add your own IP (if you are using the plugin in a local setup the IP is 127.0.0.1 or ::1, this is added in your list by default). If you set a restriction by country, then you have to select from the list of countries at least your country. Both types of restrictions work independent, so you can set only one type of restriction or both if you want. Also, you can configure the redirects to frontpage when the URLs are accessed by someone that has a restriction. The restriction is either by country, or not in the specified IPs list.


== Installation ==
* Upload `Login IP & Country Restriction` to the `/wp-content/plugins/` directory of your application
* Login as Admin
* Activate the plugin through the 'Plugins' menu in WordPress


== Screenshots ==
1. Login restriction rules amd XML-RPC authenticated methods
2. IPs restriction: allow and block specific IPs
3. Countries restriction: allow and block specific countries
4. Redirect options
5. Export and import settings, status/debug details, test country code for IP


== License ==
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.


== Frequently Asked Questions ==
None


== Upgrade Notice ==
None


== Changelog ==
= 6.6.0 =
* Tested up to 6.7
* Added the IP range options, using ~ (tilda)
* Performance updates
* Refined redirect rules
* Translations updates

See the [changelog](changelog.txt) for detailed information on changes made in the earlier versions.
