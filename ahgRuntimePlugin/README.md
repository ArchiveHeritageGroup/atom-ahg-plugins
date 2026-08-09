# ahgRuntimePlugin

Generated from `atom-framework` by `bin/build-runtime-plugin`. **Do not edit in place** -
edit the framework and regenerate.

Install this before any other AHG plugin. It provides `AhgController` (99 plugins extend
it), `AhgComponents`, the services, the routing loader, and a bundled Illuminate.

Its `install.sql` creates only the runtime's own tables. Every other plugin ships its
own schema.
