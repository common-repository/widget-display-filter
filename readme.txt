=== Widget Display Filter ===
Contributors: enomoto celtislab
Tags: widget, hide, filter, conditional tags, Widget Group block
Requires at least: 5.9
Tested up to: 5.9
Requires PHP: 7.3
Stable tag: 2.0.0
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Set the display condition for each widget. Widgets display condition setting can be easily, and very easy-to-use plugin.

== Description ==

It defines Hashtags that are associated with the display conditions. and use Hashtag to manage the display conditions of the widget. By setting the same Hashtag to multiple widgets, you can easily manage as a group. (
Of course, Hashtag does not appear at run time.)


Feature

 * Support Device filter (Discrimination of Desktop / Mobile device uses the wp_is_mobile function)
 * Support Post Format type filter
 * Support Post category and tags filter
 * Support Custom Post type filter
 * Support Widget Group block (Widget by Block after WP5.9)

Usage

1. Open the menu - "Appearance -> Widget Display Filter", and configure and manage the display conditions of Widgets.
2. Definition of Hashtags associated with the widget display conditions.
3. Open the menu - "Appearance -> Widgets", and set the display condition for each widget.
4. If you enter Hashtag in Widget Title input field, its display condition is enabled.

Notice

 * Hashtag that can be set for each widget is only one. 
 * Between Hashtag and title should be separated by a space.
 
For more detailed information, there is an introduction page.

[Documentation](https://celtislab.net/en/wp_widget_display_filter/ )

[日本語の説明](https://celtislab.net/wp_widget_display_filter/ "Documentation in Japanese")

== Installation ==

1. Upload the `widget-display-filter` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the `Plugins` menu in WordPress
3. Set up from `Widget Display Filter` to be added to the Appearance menu of Admin mode.

== Changelog ==

= 2.0.0 =
* 2022-2-22 Add : Widget Group block filter
* refactored the processing code
* discon : Custom CSS style classes to Widget

= 1.2.1 =
* 2018-03-26 Code correction for PHP 7.1 

= 1.2.0 =
* 2016-09-05  Support Multisite

= 1.1.0 =
* 2015-10-05  Support Add Custom CSS style classes to Widget
 
= 1.0.0 =
* 2015-08-05  Release
