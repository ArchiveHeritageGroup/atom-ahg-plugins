<?php
// Only load AHG assets if AHG theme is active
$activeTheme = sfConfig::get('app_theme', '');
$isAhgTheme = (strpos($activeTheme, 'arAHGTheme') !== false);

// Also check if this plugin is the active theme by checking plugins list
if (!$isAhgTheme) {
    $plugins = sfContext::getInstance()->getConfiguration()->getPlugins();
    $isAhgTheme = in_array('arAHGThemeB5Plugin', $plugins) && 
                  array_search('arAHGThemeB5Plugin', $plugins) < array_search('arDominionB5Plugin', $plugins);
}

if ($isAhgTheme):
    // Find dist bundles dynamically
    $distCss = sfConfig::get('sf_web_dir') . '/dist/css';
    $distJs = sfConfig::get('sf_web_dir') . '/dist/js';

    $ahgCss = '';
    $ahgJs = '';
    $vendorJs = '';

    // Find AHG CSS bundle
    if (is_dir($distCss)) {
        foreach (glob($distCss . '/arAHGThemeB5Plugin.bundle.*.css') as $file) {
            if (strpos($file, '.map') === false) {
                $ahgCss = '/dist/css/' . basename($file);
                break;
            }
        }
    }

    // Find JS bundles
    if (is_dir($distJs)) {
        foreach (glob($distJs . '/arAHGThemeB5Plugin.bundle.*.js') as $file) {
            if (strpos($file, '.map') === false && strpos($file, '.LICENSE') === false) {
                $ahgJs = '/dist/js/' . basename($file);
                break;
            }
        }
        foreach (glob($distJs . '/vendor.bundle.*.js') as $file) {
            if (strpos($file, '.map') === false && strpos($file, '.LICENSE') === false) {
                $vendorJs = '/dist/js/' . basename($file);
                break;
            }
        }
    }

    if ($vendorJs || $ahgJs || $ahgCss): ?>
    <?php if ($vendorJs): ?><script defer src="<?php echo $vendorJs; ?>"></script><?php endif; ?>
    <?php if ($ahgJs): ?><script defer src="<?php echo $ahgJs; ?>"></script><?php endif; ?>
    <?php if ($ahgCss): ?><link href="<?php echo $ahgCss; ?>" rel="stylesheet"><?php endif; ?>
    <?php endif; ?>
<?php endif; ?>
