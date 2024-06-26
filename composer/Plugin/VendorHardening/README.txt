The Drupal Vendor Hardening Composer Plugin
===========================================

Thanks for using this Drupal component.

You can participate in its development on Drupal.org, through our issue system:
https://www.drupal.org/project/issues/drupal

You can get the full Drupal repo here:
https://www.drupal.org/project/drupal/git-instructions

You can browse the full Drupal repo here:
https://git.drupalcode.org/project/drupal

What does it do?
----------------

This Composer plugin does two things:

1) It removes extraneous directories from the project's vendor directory.
They're typically directories which might contain executable files, such as test
directories.

This sort of processing is required for projects that have a vendor directory
inside the HTTP server docroot. This is a common layout for Drupal.

By default, the plugin knows how to clean up packages for Drupal core, so you
can require drupal/core-vendor-hardening in your project and the rest will
happen automatically.

The plugin can also be configured to clean up additional packages using the
project's composer.json extra field.

This plugin can also clean up packages that were installed outside of the
vendor directory, using composer/installers. This allows users to configure the
plugin to clean up, for instance, Drupal extensions and Drupal core.

2) The plugin also adds .htaccess file to the root of the
project's vendor directory. The file will perform due diligence to keep the
web server from serving file from within the vendor directory.

How do I set it up?
-------------------

Require this Composer plugin into your project:

    composer require drupal/core-vendor-hardening

When you install or update, this plugin will look through each package and
remove directories it knows about.

You can see the list of default package cleanups for this plugin in Config.php.
If you discover that this list needs updating, file an issue about it:
https://www.drupal.org/project/issues/drupal

In addition to the default list of packages, you can configure the plugin using
the root package's composer.json extra field, like this:

    "extra": {
      "drupal-core-vendor-hardening": {
        "vendor/package": ["test", "documentation"]
      }
    }

The above code will tell the plugin to remove the test/ and documentation/
directories from the 'vendor/package' package when it is installed or updated.

For packages installed outside of the vendor directory, such as those installed
by composer/installers, the paths to remove should be relative to the package
base. As an example, a Drupal module package named drupal/module_name might be
installed by composer/installers to web/modules/contrib/module_name/. Cleanup
paths specified for this package might look like this:

    "extra": {
      "drupal-core-vendor-hardening": {
        "drupal/module_name": ["tests", "src/Tests"]
      }
    }

This would then cause the plugin to try and remove
web/modules/contrib/module_name/tests and
web/modules/contrib/module_name/src/Tests.
