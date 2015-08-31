WebCraG
A web craft guide for minetest

* Requirement

On web server side :
PHP > 5.3 with GD support

On minetest server side :
Linux / Unix operating system 

* Installation

- Create an 'export' directory that is writeable by minetest mods and readable by apache webserver using php;
- Copy minetest-mod/webcrag in your mods directory;
- Edit the config.lua file and set 'export' directory path;
- Copy webserver directory content somewhere on your webserver;
- Make sure 'img' directory is writeable by apache/php.
- Edit config.php and set 'export' directory path.

* Test

Enable webcrag mod on minetest server and start server.
Once server started, images and data from minetest should be present in mtdata.json file and img subdirectory in export directory.
index.php page should show the items index.
