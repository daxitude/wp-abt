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
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easy A/B testing for your WordPress Pages.

== Description ==

This plugin allows you to easily A/B test content on your WordPress Pages (support for other post types may be on the way). Create an experiment, define a goal, add variations, hit the start button!

This plugin is still under development. However, you are, of course, free to use and modify it as you please.

== Installation ==

See [Installing Plugins](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

== Frequently Asked Questions ==

= How do I use this? =

An answer to that is coming.

== Screenshots ==

tbd

=== TODO ===

* unit testing (not exactly tdd here :/)
* documentation
* verify dependencies, PHP and WP
* additional test types eg theme, css, js, multivariate
* trying this algo for serving variations?: http://stevehanov.ca/blog/index.php?id=132
* validation - enforce uniqueness on variation.post_id
* "test mode" - run without cookies so that visits/conversions can be simulated
* maybe don't use as many rows in wp_options ?
* hook into post deletion actions to deal with issue when a variation or goal page is deleted
* expand experiments to other post types
* send data to Google Analytics and/or other services?
* reduce the number of extra trips to the database when a visitor requests a Page. Some ideas: autoload an array of variation/conversion page ids or cache on first request, make writes for visits/conversions non-blocking (async?)
* filter traffic from bots??
* store visits/conversions individually so time trends can be observed
