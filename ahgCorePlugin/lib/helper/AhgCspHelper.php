<?php

/**
 * Helpers for emitting inline CSS and JavaScript under AtoM's Content Security
 * Policy.
 *
 * WHY THIS EXISTS
 *
 * AtoM ships style-src and script-src with a nonce and without 'unsafe-inline'
 * or 'unsafe-hashes'. Two consequences follow, and both have cost real time:
 *
 *   1. An inline <style> or <script> without the nonce is dropped by the browser.
 *      Silently: nothing is logged server side and no page fails, the styling
 *      simply is not there.
 *
 *   2. A nonce cannot rescue a style attribute. Nonces apply to elements, so
 *      style="width: 50%" is blocked no matter what, and the only fixes are a
 *      class or setting the property through the CSSOM, which CSP does not cover.
 *
 * Both were invisible on the development instance for a week, because it sent
 * Content-Security-Policy-Report-Only. Report-only reports the violation and then
 * applies the style anyway, so every check passed while the defect sat there. Do
 * not trust a report-only instance to tell you a plugin is CSP-clean.
 *
 * WHAT TO USE
 *
 *   ahg_style_block('.thing { height: 8px; }')   a nonced <style>
 *   ahg_script_block('doSomething();')           a nonced <script>
 *   ahg_csp_nonce_attr()                         the bare attribute, if you are
 *                                                building the tag yourself
 *
 * For a value computed per record, put it in a data attribute and apply it with
 * JavaScript rather than reaching for a style attribute:
 *
 *   <div class="bar" data-pct="<?php echo $pct; ?>"></div>
 *   ahg_script_block("document.querySelectorAll('.bar').forEach(function (el) {
 *       el.style.width = (parseFloat(el.dataset.pct) || 0) + '%';
 *   });");
 */

if (!function_exists('ahg_csp_nonce_attr')) {
    /**
     * The nonce attribute for an inline <style> or <script>, or an empty string
     * when no policy is in force.
     *
     * sfConfig holds it as "nonce=abc123" rather than a bare value, which is why
     * this is not simply a concatenation - getting that wrong produces a tag the
     * browser rejects, which looks exactly like having no nonce at all.
     */
    function ahg_csp_nonce_attr(): string
    {
        $nonce = sfConfig::get('csp_nonce', '');

        if (!$nonce) {
            return '';
        }

        return ' '.preg_replace('/^nonce=/', 'nonce="', $nonce).'"';
    }
}

if (!function_exists('ahg_style_block')) {
    /**
     * An inline <style> carrying the CSP nonce.
     *
     * @param string $css rules, without the surrounding <style> tags
     */
    function ahg_style_block(string $css): string
    {
        if ('' === trim($css)) {
            return '';
        }

        return '<style'.ahg_csp_nonce_attr().'>'."\n".$css."\n".'</style>';
    }
}

if (!function_exists('ahg_script_block')) {
    /**
     * An inline <script> carrying the CSP nonce.
     *
     * @param string $js statements, without the surrounding <script> tags
     */
    function ahg_script_block(string $js): string
    {
        if ('' === trim($js)) {
            return '';
        }

        return '<script'.ahg_csp_nonce_attr().'>'."\n".$js."\n".'</script>';
    }
}
