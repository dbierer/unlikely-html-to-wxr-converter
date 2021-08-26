# HTML to WXR Conveter
WordPress plugin that converts standalone HTML file(s) to WXR import files.
In the configuration file you can specify delimiters that tell the plugin how to extract content from the HTML pages.
Using configuration you can extract content and strip out extraneous headers or footers.

## Configuration file options
The config file is `src/config/config.php`.
Here is a summary of the primary configuration keys:

### export
The `export::rss` key should only be updated when the WXR specification changes.
The `export::channel` key needs to be completed with the appropriate values
* Leave the `export::channel::pubDate` key as-is
* Only update the `export::channel::generator` key if a new version is available

## Importing WXR files
To import, proceed as follows:
* Open a terminal window/command prompt
* Change to your main WordPress installation directory
* Perform the import, where `PATH` is the path to the WXR files you created using this plugin
```
wp import PATH
```
