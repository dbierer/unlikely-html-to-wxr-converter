unlikely/html-to-wxr
====================

Converts HTML to WXR import files

[![Build Status](https://travis-ci.org/unlikely/html-to-wxr.svg?branch=master)](https://travis-ci.org/unlikely/html-to-wxr)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing) | [Support](#support)

# HTML to WXR Conveter
WP-CLI command that converts standalone HTML file(s) to WXR import files.
In the configuration file you can specify delimiters that tell the plugin how to extract content from the HTML pages.
Using configuration you can extract content and strip out extraneous headers or footers.

## WP-CLI Usage
The generic usage is as follows:
```
wp html-to-wxr --config=/path/to/config/file --dest=/path/to/store/WXR/files [--src=STARTING_DIR | --file=SINGLE_FILE] [--ext=HTML]
```

Alternate usage is as follows:
```
wp html-to-wxr -c /path/to/config/file -d /tmp [-s STARTING_DIR] [-f SINGLE_FILE] [-x HTML]
```

### Options summary

| Option   | Optional | Notes |
| :------- | :------- | :---- |
| --config | N | Path to the configuration file that controls how the conversion takes place |
| --dest   | N | Directory where WXR files will be stored for later import |
| --src    | Y | Use this option to specify where to start converting.  If specified, to not use the `--file` option |
| --file   | Y | Use this option if you only want to convert a single file.  If specified, to not use the `--dir` option |
| --ext    | Y | Use this if the extension for files to be converted is not "HTML".  If you want to convert multiple extensions, separate them with a comma. |

Example converting multiple extensions in /httpdocs directory, writing to /tmp:
```
wp html-to-wxr -c /config/config.php -d /tmp  -s /httpdocs -x html,phtml
```


## Configuration file options
The config file is `src/config/config.php`.
Here is a summary of the primary configuration keys:

### Export
The `export::rss` key should only be updated when the WXR specification changes.
The `export::channel` key needs to be completed with the appropriate values
* Leave the `export::channel::pubDate` key as-is
* Only update the `export::channel::generator` key if a new version is available

## Importing WXR Files
To import, proceed as follows:
* Open a terminal window/command prompt
* Change to your main WordPress installation directory
* Perform the import, where `PATH` is the path to the WXR files you created using this plugin
```
wp import PATH
```

## WXR Format
Here's an article that explains WordPress WXR format:
[https://devtidbits.com/2011/03/16/the-wordpress-extended-rss-wxr-exportimport-xml-document-format-decoded-and-explained/](https://devtidbits.com/2011/03/16/the-wordpress-extended-rss-wxr-exportimport-xml-document-format-decoded-and-explained/)

## Installing

Installing this package requires WP-CLI v2.5 or greater. Update to the latest stable release with `wp cli update`.
You also need to install the `wordpress-importer` plugin or equivalent (needs to accept WXR files).

Once you've done so, you can install the latest stable version of this package with:

```bash
wp package install unlikely/html-to-wxr:@stable
```

To install the latest development version of this package, use the following command instead:

```bash
wp package install unlikely/html-to-wxr:dev-master
```

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

For a more thorough introduction, [check out WP-CLI's guide to contributing](https://make.wordpress.org/cli/handbook/contributing/). This package follows those policy and guidelines.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/unlikely/html-to-wxr/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/unlikely/html-to-wxr/issues/new). Include as much detail as you can, and clear steps to reproduce if possible. For more guidance, [review our bug report documentation](https://make.wordpress.org/cli/handbook/bug-reports/).

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/unlikely/html-to-wxr/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, [please follow our guidelines for creating a pull request](https://make.wordpress.org/cli/handbook/pull-requests/) to make sure it's a pleasant experience. See "[Setting up](https://make.wordpress.org/cli/handbook/pull-requests/#setting-up)" for details specific to working on this package locally.

## Support

GitHub issues aren't for general support questions, but there are other venues you can try: https://wp-cli.org/#support


