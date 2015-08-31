Web Craft Guide Minetest Mod
============================

This mod is not intended to be used stand alone, it comes with a PHP part for online craft guide display.

* Configuration

Edit the config.lua file and choose the eport path. Export path is a directory that can be written by the webcrag mod and read by the php on webserver.

Eport path has to be set also in config.php file on the web server.

* Usage

Once export path is set and mod enabled, there is nothing to do. At server start, the mod will export data and images from minetest game into export directory. This data will be used by the php program to set up web pages.
