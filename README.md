runcommand/precache
===================

Proactively download and cache core, theme, and plugin files for later installation.


Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing)

## Using

This package implements the following commands:

### wp precache core

Proactively download and cache WordPress core.

~~~
wp precache core [--version=<version>] [--locale=<locale>]
~~~

**OPTIONS**

	[--version=<version>]
		Specify the version to cache.

	[--locale=<locale>]
		Specify the language to cache.



### wp precache plugin

Proactively download and cache one or more WordPress plugins.

~~~
wp precache plugin [<plugin>...] [--version=<version>]
~~~

**OPTIONS**

	[<plugin>...]
		One or more plugins to proactively cache.

	[--version=<version>]
		Specify the version to cache.



### wp precache theme

Proactively download and cache one or more WordPress themes.

~~~
wp precache theme [<theme>...] [--version=<version>]
~~~

**OPTIONS**

	[<theme>...]
		One or more themes to proactively cache.

	[--version=<version>]
		Specify the version to cache.



## Installing

Installing this package requires WP-CLI v0.23.0 or greater. Update to the latest stable release with `wp cli update`.

Once you've done so, you can install this package with `wp package install runcommand/precache`

## Contributing

Code and ideas are more than welcome.

Please [open an issue](https://github.com/runcommand/precache/issues) with questions, feedback, and violent dissent. Pull requests are expected to include test coverage.
