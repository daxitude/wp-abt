/*  Copyright 2012 daxitude@gmail.com

    This program is free software; you can redistribute it and/or modify
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

== Installation ==

See [Installing Plugins](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

== Frequently Asked Questions ==

= How do I use this? =

An answer to that is coming.

== Screenshots ==

tbd

=== TODO ===

* wp_nonce
* unit testing
* testing on varied WP installs
* documentation
* verify dependencies, PHP and WP
* validation - enforce uniqueness on variation.post_id
* "test mode" - run without cookies so that visits/conversions can be simulated
* maybe don't use as many rows in wp_options ?
* hook into post deletion actions
* expand test to other post types
* send data to Google Analytics and/or other services?
