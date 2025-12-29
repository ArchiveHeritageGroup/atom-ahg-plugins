# AtoM Dominion BS5 Theme Plugin Skeleton

Initial skeleton for an AtoM theme plugin, extending arDominionB5Plugin without 
modifications.

Requires an AtoM version of 2.7, or up, to include such theme.

## Clone repository to AtoM's plugin folder

Clone repository **_within AtoM's plugin folder_** and delete git related files.

  ```
  git clone --depth=1 https://github.com/artefactual-labs/arThemeB5Plugin.git plugins/arThemeB5Plugin

  rm -rf plugins/arThemeB5Plugin/.git plugins/arB5ThemePlugin/README.md
  ```

### Modify the plugin:

  - Rename the plugin folder.
  - Rename the [config filename](config/arThemeB5PluginConfiguration.class.php).
  - Rename the [config class name](config/arThemeB5PluginConfiguration.class.php#L23).
  - Modify the [theme summary and version](config/arThemeB5PluginConfiguration.class.php#L25-26).
  - Update the [theme image](images/image.png) (shown in the themes page).

> **IMPORTANT:** config filename and config class name must be the same as the plugin folder name
>
> ex. for `plugins/arThemeB5Plugin`, a config file named `arThemeB5PluginConfiguration.class.php`
> is required with a class named `arThemeB5PluginConfiguration`

### Extend/customize the plugin:

  - Add styling changes directly to, or link stylesheets in, [main SCSS file](scss/main.scss).
  - Link scripts to [main JS file](js/main.js).
  - Copy any actions and templates files required to the new `plugins/arThemeB5Plugin` directory to overwrite files.
    - copy files from `plugins/arDominionB5Plugin`, if available, and from `apps/qubit` only when
      required files are not available in `plugins/arDominionB5Plugin` [due to precedence]
    - place copied files into `plugins/arThemeB5Plugin/modules` with the same path

      > Examples
      > 
      > `/apps/qubit/templates/_footer.php` => `plugins/arThemeB5Plugin/templates/_footer.php`
      >
      > `/plugins/arDominionB5Plugin/modules/informationobject/templates/_editActions.php`
      > => `plugins/arThemeB5Plugin/modules/informationobject/templates/_editActions.php`

### Build the BS5 theme assets:

  ```
  npm install
  npm run build

  php symfony cc       # clear cache [OPTIONAL]
  ```
