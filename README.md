Jadu Composer Package Custom Installer
======================================

A custom installer for Composer packages.

It allows the package to automatically install themselves into a Jadu implementation Project.

The installation happens when `composer update` is run on a local Mac or on the build server,
and prepares the Project repo so that the Package is included as needed in the Project's release package.


The package has control to achieve everything needed to install itself, including:

 - *copy files* from the Package into the Project — with control over overwriting behaviour
 - *run scripts* from within the Package to do custom things
 - specify *permissions* required on the server, by adding to the config/permission/custom file which Meteor reads from
 - add include/exclude rules to the Project's *build.xml*, controlling what gets included in the release package

### Some actions are also performed automatically:

 - any *database migration scripts* in the Package are copied into the Project's upgrades/migrations folder, with an updated timestamp. Example use case: if the package requires its own database tables, these can be handled via a migration script.

 - files copied into the Project are added to the *.gitignore* file so they don't get committed with the Project. This can be disabled per file — e.g. for “starting point” templates which will need to be customised for the Project, which will then want to be committed.

 - files copied into the Project are automatically added to *build.xml* so they get included in the Meteor release package. This can be disabled per file.

 - any **_VERSION files* in the root of the package are copied into the Project's root, so these will appear on the Control Centre's version.php page, so the package versions installed can be easily seen.


All file changes made by the installer are visible to the developer via Git, and it is the responsibilty of the developer to commit those changes once they are happy with them.

It is still Meteor that will package files, execute migrations and set permission on the server. Widget Factory just adds the appropriate configuration that Meteor requires to do this to include a custom package.


Example — Widget Factory
------------------------

Widget Factory is a package that allow rapid building of robust widgets simply through configuration.

With this custom Composer installer, Widget Factory can be added to a project with two simple steps:

1. Add a small section to the Project's `composer.json` file to declare widgets using Widget Factory:
    ```json
        "extra": {
            "jadu-widget-factory": {
                "widgets": "999 997"
            }
        }
    ```
2. Run `composer require jadu/widget-factory`

The Widget Factory package will automatically do the following:
1. copy secure.js from within the Package into var/widgets/997/secure.js and var/widgets/999/secure.js
2. copy a folder from the Package into `public_html/jadu/widget-factory` so CSS is available
3. tell Git to ignore these secure.js files and `public_html/jadu/widget-factory` folder
4. copy the WIDGET_FACTORY_VERSION file into the Project root (and add to .gitignore)
5. Include all these files/folders, along with the Widget Factory class files in vendor/jadu/widget-factory in the Meteor package by adding them to build.xml

### These are the steps required to configure Widget Factory to install itself:

1. Modify `composer.json` to add the following:

    ```json
        "type": "jadu-module",
        "require": {
            "jadu/composer-installer": "~1"
        },
        "extra": {
            "jadu-install": {
                "copy": {
                    "resources/public": "public_html/jadu/widget-factory"
                },
                "scripts": {
                    "install": [
                        "Jadu\\Widget\\Installer::installForWidgets"
                    ]
                },
                "package-include": [
                    "vendor/jadu/widget-factory"
                ]
            }
        }
    ```

2. Add an installation script to copy the secure.js into each widget folder as appropriate, having read
from the Project's `composer.json` which widgets are using Widget Factory

Most packages wouldn't require this step, as most installation tasks can be handled by the standard Jadu `composer-installer` features available. But the ability to run custom scripts allows packages to handle their own installation by whatever logic is required.

Widget Factory doesn't require any database changes, so there are no migration scripts to copy.



Feature Documentation
---------------------

A Package configures what the Composer Installer will do via the Package's `composer.json`, within a `jadu-install` hash within `extra`.

### Example

```json
"extra": {
    "jadu-install": {
        "copy": {
            "resources/some_script.php": {
                "destination": "public_html/site/scripts/some_script.php",
                "overwrite": false,
                "ignore": false
            },
            "resources/public": "public_html/jadu/my-little-package"
        },
        "scripts": {
            "install": [
                "npm install && npm run build",
                "Jadu\\MyLittlePackage\\Installer::doSomeSpecialStuff"
            ]
        },
        "permissions": {
            "public_html/jadu/custom": "rR",
            "vendor": "x",
            "vendor/jadu": "x",
            "vendor/jadu/my-little-package": "x"
        },
        "package-include": [
            "vendor/jadu/my-little-package/**"
        ],
        "package-exclude": [
            "vendor/jadu/my-little-package/tests/**"
        ]
    }
}
```


Copy
----
List files/folder to copy.

By default, files and folders copied are added to .gitignore and included in the Meteor package (via build.xml)

The array key is the source file (relative to the package root path),
the array value is either
(a) the destination (string, relative to the root install dir), or
(b) an array, with keys
    string destination Destination to copy file to, relative to the root install dir
    bool overwrite    Optional (defaults to true). Whether to overwrite existing file
    bool ignore    Optional (defaults to true). If true, the copied file will be added to .gitignore
    bool include    Optional (defaults to true). If true, the copied file will be included in the Meteor package (i.e. added to the build.xml fileset)

Scripts
-------
Supports 'install' and 'uninstall' events.
They work the same as standard Composer events. See https://getcomposer.org/doc/articles/scripts.md

Scripts will be executed from the package's folder (e.g. in vendor/foo/bar/)

Scripts are run *before* other actions (such as copy) are performed — so you can copy build artifacts.

Permissions
-----------
Permissions rules can be added to config/permissions/custom so Meteor will set these when the package is deployed.

