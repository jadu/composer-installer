install as normal into vendor/

Actions
-------
 - copy into other places
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

Scripts will be executed from the package's folder (e.g. in vendor/foo/bar/)

Scripts are run *before* other actions (such as copy) are performed — so you can copy build artifacts.


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
