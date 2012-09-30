/*  This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

=== Plugin Name ===
Contributors: daxitude
Tags: a/b testing, metrics, a/b test
Requires at least: 3.4
Tested up to: 3.4.2
Stable tag: 0.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Split test your way to conversion nirvana without ever leaving your WordPress admin.

== Description ==

This plugin allows you to easily A/B test content on your WordPress Pages (support for other post types may be on the way). Create an experiment, define a goal, add variations, hit the start button!

This plugin is still under development. However, you are of course free to use and modify it as you please.

== Installation ==

See [Installing Plugins](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

This plugin hasn't been submitted to the WordPress Plugin Directory yet. In the mean time, you can install it this way:

1. Download the zip (or clone the repo) from [Github](http://github.com/daxitude/wp-abt) and drop it into your site's wp-content/plugins directory.
1. Navigate to your site's Admin->Plugins section (wp-admin/plugins.php) and activate the plugin.
1. Click on "A/B Tests", located at the bottom of your admin menu
1. Many of the plugin's admin screens have contextual help


== Frequently Asked Questions ==

= How do I use this? =

An answer to that is coming. In the mean time check out the contextual help on the plugin's admin pages.

= How does it work ? =

Let's say you have a page at yoursite.com/tour, where you want to test whether adding a video increases sign-ups. First, create the alternate page with the content, title, and any post meta that you need. You can give it any permalink you like, for this example let's make it /tour-1.

Navigate to A/B Tests in your WordPress admin and create a new experiment. In this example, maybe the goal page is at /thank-you, where visitors are directed after successfully completing a sign-up form. Add the two variations to the experiment, making sure to set the page at /tour as the first variation so that it's the base (control) variation. Then, start the experiment!

When /tour is visited, the plugin checks to see whether the visitor has been to the page previously, and if so they are served the same variation they've seen on prior visits (maybe it's /tour, maybe it's /tour-1). If they are a new visitor, the plugin fetches the variation with the lowest view count,  serves it up, and increments the variation's view count.

(aside: there's room for improvement in this algorithm. Some links are provided in the TODO below)

When /thank-you is visited, the plugin checks to see whether the visitor has been assigned a variation and is visiting /thank-you for the first time. If these conditions are met, a conversion is assigned to the visitor and the appropriate variation's conversion count is incremented.

You can check on experiment progress at any time from A/B Tests in your WP admin and decide for yourself when to end an experiment.


== Screenshots ==

tbd

=== TODO ===

* unit testing (not exactly tdd here :/)
* documentation
* additional test types eg theme, css, js, multivariate
* choose an algorithm for serving variations (maybe http://stevehanov.ca/blog/index.php?id=132, or http://jeffbollinger.net/2010/08/ab-testing-sample-sizes-the-convergence-method/)
* validation - enforce uniqueness on variation.post_id
* "test mode" - run without cookies so that visits/conversions can be simulated
* maybe don't use as many rows in wp_options ?
* hook into post deletion actions to deal with issue when a variation or goal page is deleted
* expand experiments to other post types
* send data to Google Analytics and/or other services?
* reduce the number of extra trips to the database when a visitor requests a Page. Some ideas: autoload an array of variation/conversion page ids or cache on first request, make writes for visits/conversions non-blocking (async?)
* filter traffic from bots??
* store visits/conversions individually so time trends can be observed
* pagination for the experiments page
* could use post meta to configure and store variation and conversion information. wouldn't even need the variations table??
* allow for setting % of visitors to be included in any given experiment