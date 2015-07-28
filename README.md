install as normal into vendor/

Actions
-------
 - copy/symlink into other places
 - write .gitignore for anything we add

 - run scripts to do custom things
 - copy migration scripts into upgrades/migrations
 - ensures x permissions -> write to config/permission/custom
 - add to build.xml

Examples
--------
package e.g. widget-factory
    defines a script to be run
        look through widgets (or read composer.json)
        if any are marked as needing to be widget factory, copy JS


package e.g. lost-animals
    defines files to be symlinked
    defines folders to create



composer.json

Copy
----
List files/folder to copy.

The array key is the source file (relative to the package root path),
the array value is the destination (relative to the root install dir)

Scripts
-------
Supports 'install' and 'uninstall' events.
They work the same as standard Composer events. See https://getcomposer.org/doc/articles/scripts.md


```json
"extra": {
    "jadu-install": {
        "copy": {
            "resources/public": "public_html/jadu/widget-factory"
        },
        "scripts": {
            "install": [
                "npm install && npm run build"
            ],
            "uninstall": [

            ]
        }
    }
}
```
