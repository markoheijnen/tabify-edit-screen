=== Tabify Edit Screen ===
Contributors: CodeKitchen, markoheijnen, tara-van-der-wel
Donate link: https://codekitchen.eu/donate
Tags: tabs, edit, admin, post, page, posts, tabs plugin, admin tabs
Requires at least: 3.7
Tested up to: 4.5
Stable tag: 0.9.5

Enable tabs in the edit screen and manage them from the back-end.

== Description ==

When you've got lots of post meta-boxes, your post edit screen can become difficult to search. Make your post edit screen easier to navigate and manage by creating a set of tabs, with the Tabify Edit Screen plugin. And manage it all from the WordPress back-end!

There are still lots of things changing in the code base. There are some hooks you can use but they can change from implementation till 1.0. I will always try to maintain backwards-compatible but till 1.0 I can't be sure for that.

== Installation ==

1. Upload the folder `tabify-edit-screen` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the Settings -> Tabify edit screen to enable tabs for a specific post type
 
== Frequently Asked Questions ==

= Can you define metaboxes that will always be visible? =

At this moment the title and submit box are always visible. You can change this by using the following filters:
 * tabify_default_metaboxes - $defaults, $post_type
 * tabify_default_metaboxes_$post_type - $defaults

= Can I place the tabs under the title input field? =

Yes, you can use the filter 'tabify_tab_location' and passing the value 'after_title' to it.
You can also check on the second argument what the location is. Now always 'posttype'

= Does it work with all plugins? =
Since version 0.9.0 it should work for almost all plugins. Unless they do extra checks it should work.
Before there was a filter 'tabify_plugin_support' which still exists but shouldn't be used anymore.

= What happen with new metaboxes? =
At this moment new metaboxes are places on the last tab. You can go to the settings screen to reorder the metabox. You can change which tab it should go like with meta boxes that don't show up like Types or WordPress SEO. You do this with the filter 'tabify_unattached_metaboxes_index'.

== Screenshots ==

1. How it would look like after enabling this plugin
2. The settings page where you can manage the tabs for each posttype

== Changelog ==

= 0.9.5 ( 2016-03-03 ) =
* Only load features once. Caused when plugins set a new current_screen.

= 0.9.4 ( 2016-02-28 ) =
* Rewrite of smart meta box detection to use Javascript instead.
* Clear Detection cache on needed moments.
* Increase caching time for smart detection to a week.
* Disabled plugin support which accidentally got enabled in 0.9.3
* Ability to enable/disable the post types tabs through the filter 'tabify_tab_posttype_show'
* Fix notices on WooCommerce install screen
* Check if metaboxes got removed to prevent notices
* Remove action ‘tabify_tabs’ due to same filter.

= 0.9.3 ( 2016-02-18 ) =
* Fix undefined notice on settings page. Props Juliette Reinders Folmer
* Fix typo in detection transient name. Props Juliette Reinders Folmer
* Fix vertical tabs on setting page display next to each other in WordPress 4.4
* Added back plugin support with filter 'tabify_plugin_support'

= 0.9.2 ( 2015-11-14 ) =
* Decrease timeout for smart meta box detection requests
* Disable smart meta box detection after 3 failures at the same time
* Stop all other requests after 3 failures

= 0.9.1 ( 2015-11-14 ) =
* Fix issue with older WordPress version
* Expire detection transient after 24 hours

= 0.9.0 ( 2015-11-14 ) =
* Add ability to set permission based on user role per tab. Sponsored by <a href="http://www.NWConsulting.co.uk">NW Consulting</a>
* Added smart meta box detection
* Removed the filter 'tabify_plugin_support'
* New filter 'tabify_tabs_tab_title' which can be used to translate the tab title
* Code clean ups
* Updated readme info with new donate link
* Tested up to WordPress 4.4

= 0.8.3 ( 2015-07-27 ) =
* Fix editor after switching tabs
* Loading minified files

= 0.8.2 ( 2014-07-27 ) =
* Make the settings UI responsive and made some other little UI improvements. 
* Additional plugin support for WordPress SEO

= 0.8.1 ( 2014-03-26 ) =
* Allow some HTML in the tab title.
* Fix border issue on the pos type selection in the settings page

= 0.8.0 ( 2013-12-25 ) =
* Fix 3.8 styling

= 0.7.1 ( 2013-09-26 ) =
* Fix possible issues with meta boxes showing on every tab
* Add textdomain to the right places
* Fix (strict) notices in code

= 0.7.0 ( 2013-06-14 ) =
* When submit box isn't always showed then do show a submit button in the tabs
* Only hide metaboxes when there is javascript support
* Change some styling when MP6 is been used
* Add extra span around meta box titles in the settings page to look better in MP6
* Add support for WPML

= 0.6.0 ( 2013-02-24 ) =
* Complete rewrite of settings screen. Most of HTML is now in the base class
* All unattached metaboxes will be moved to the last tab
* Fix setting current tab when editing a post
* Added some extra string escaping
* Specify screen icon

* Added French translation by Jean-Christophe Brebion
* Updated Dutch translation

= 0.5.3 ( 2013-01-07 ) =
* Fix issue that meta boxes are unchecked in screen options

= 0.5.2 ( 2012-12-31 ) =
* Default tabs was broken

= 0.5.1 ( 2012-12-31 ) =
* Move tabs to top again. That is the default state

= 0.5.0 ( 2012-12-31 ) =
* Improved deleting a tab from the setting screen
* Fix bugs caused when deleting a tab and moving metaboxes to different tabs
* Make CSS more explicit so that plugins like ACF don't show up on first load. (Thanks elliotcondon)
* Ability to place the tabs under the title input field with the filter 'tabify_tab_location' and passing 'after_title'
* Add body class for tab location
* Fix update settings and adding slashes to the title
* Removing several referenced hooks
* Fix some notices on edge cases
* Fix javascript error on settings page

* Also created a repository for a forked Types that works with this plugin: https://github.com/markoheijnen/types/tree/use-hook-add_meta_boxes

= 0.4.1 ( 2012-11-9 ) =
* Fix javascript code so you are now able to create new tabs again

= 0.4.0 ( 2012-11-9 ) =
* Complete fresh new UI for the settings screen
* Fix possible bug for not showing author metabox in the settings screen
* Fix notice on edit screen when no items where inside a tab
* Fix issue that not all post types are showing
* Now able to hide a metabox as an user and that the plugin will listen to that. Thank Elles for nagging me about to fix that ;)
* Ability to remove plugin support and isn't on by default anymore. Can be enabled with the filter "tabify_plugin_support"
* Added plugin support for Members and WordPress SEO. (Thanks sc0ttkclark for his help )
* Added Dutch translation

* Ability to modify tabs through the filter tabify_tabs
* Ability to add more tab support like post types with the filter 'tabify-edit-screen-settings-tabs'
* Ability to add more options with the action 'tabify-settings-posttype' and filter 'tabify-settings-update'

= 0.3.0 ( 2012-4-7 ) =
* Add support for the plugin "Types"
* Removed the filter tabify_default_metaboxes_$posttype and changed it for the action tabify_add_meta_boxes
* Improved the way to delete a tab by showing a remove link ( Javascript only )
* Make the tab title translatable with tabify-edit-screen-tab as domain
* The text in javascript is now also translatable
* Removed duplicated code

= 0.2.0 ( 2012-4-6 ) =
* Added security checks on the setting screen
* Create a new tab now also works on all post types
* When you save the changes you go back to the selected tab. This also work for the edit screens
* Setting page works when javascript isn't supported
* You can now delete a tab when all the metaboxes are removed and the title is empty. Will be improved in later version
* New metaboxes will always be showed in the setting page
* The setting page now can be changed from a touch device
* Fix issue where you couldn't move metaboxes in a new created tab

= 0.1.0 ( 2012-4-2 ) =
* First version to show people the possibilities
