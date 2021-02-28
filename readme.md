# Tabify Edit Screen

[![Build Status](https://travis-ci.org/markoheijnen/tabify-edit-screen.svg?branch=master)](https://travis-ci.org/markoheijnen/tabify-edit-screen)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/markoheijnen/tabify-edit-screen/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/markoheijnen/tabify-edit-screen/?branch=master)
[![Average time to resolve an issue](http://isitmaintained.com/badge/resolution/markoheijnen/tabify-edit-screen.svg)](http://isitmaintained.com/project/markoheijnen/tabify-edit-screen "Average time to resolve an issue")
[![Percentage of issues still open](http://isitmaintained.com/badge/open/markoheijnen/tabify-edit-screen.svg)](http://isitmaintained.com/project/markoheijnen/tabify-edit-screen "Percentage of issues still open")

Requires at least: 3.7
Tested up to: 5.6.2
Stable tag: 1.0.0

Enable tabs in the edit screen and manage them from the back-end.

## Description

When you've got lots of post meta-boxes, your post edit screen can become difficult to search. Make your post edit screen easier to navigate and manage by creating a set of tabs, with the Tabify Edit Screen plugin. And manage it all from the WordPress back-end!

There are still lots of things changing in the code base. There are some hooks you can use but they can change from implementation till 1.0. I will always try to maintain backwards-compatible but till 1.0 I can't be sure for that.

## Installation

1. Upload the folder `tabify-edit-screen` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the Settings -> Tabify edit screen to enable tabs for a specific post type
 
## Frequently Asked Questions

### Can you define metaboxes that will always be visible?

At this moment the title and submit box are always visible. You can change this by using the following filters:
 * tabify_default_metaboxes - $defaults, $id, $type
 * tabify_default_metaboxes_$type - $defaults, $id

The $id is for example the post type name when $type is posttypes.

### Can I place the tabs under the title input field?

Yes, you can use the filter 'tabify_tab_location' and passing the value 'after_title' to it.
You can also check on the second argument what the location is. Now always 'posttype'

### Does it work with all plugins?
Since version 0.9.0 it should work for almost all plugins. Unless they do extra checks it should work.
Before there was a filter 'tabify_plugin_support' which still exists but shouldn't be used anymore.

### What happen with new metaboxes?
At this moment new metaboxes are places on the last tab. You can go to the settings screen to reorder the metabox. You can change which tab it should go like with meta boxes that don't show up like Types or WordPress SEO. You do this with the filter 'tabify_unattached_metaboxes_index'.
