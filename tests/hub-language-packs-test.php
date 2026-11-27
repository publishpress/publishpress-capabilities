<?php

if (PHP_SAPI !== 'cli') {
    exit;
}

$root = dirname(__DIR__);
$isPro = is_file($root . '/capabilities-pro.php');
$withStandaloneFree = in_array('--standalone-free', $argv, true);
$hooks = [];
$uploadsError = false;
$fixtureRoot = sys_get_temp_dir() . '/capabilities-language-packs-' . bin2hex(random_bytes(6));
$fixtureFiles = [];
$packDirectory = $fixtureRoot . '/publishpress/language-packs';
mkdir($packDirectory, 0777, true);

define('ABSPATH', $fixtureRoot . '/');
define('WP_LANG_DIR', $fixtureRoot . '/languages');
define('CAPSMAN_VERSION', '2.55.0');
define('PUBLISHPRESS_CAPS_PRO_VERSION', '2.55.0');

function add_filter($hook, $callback, $priority = 10, $arguments = 1)
{
    $GLOBALS['hooks'][$hook][$priority][] = $callback;
}

function add_action($hook, $callback, $priority = 10, $arguments = 1)
{
    add_filter($hook, $callback, $priority, $arguments);
}

function apply_filters($hook, $value, ...$arguments)
{
    $priorities = $GLOBALS['hooks'][$hook] ?? [];
    ksort($priorities);
    foreach ($priorities as $callbacks) {
        foreach ($callbacks as $callback) {
            $value = call_user_func($callback, $value, ...$arguments);
        }
    }
    return $value;
}

function determine_locale()
{
    return 'pt_BR';
}

function wp_normalize_path($path)
{
    return str_replace('\\', '/', $path);
}

function wp_upload_dir($time = null, $create = true)
{
    return [
        'basedir' => $GLOBALS['fixtureRoot'],
        'error' => $GLOBALS['uploadsError'],
    ];
}

function pp_capabilities_pro_is_free_plugin_active()
{
    return $GLOBALS['withStandaloneFree'];
}

function get_current_screen()
{
    return (object) ['id' => 'users_page_pp-capabilities-roles-network'];
}

function pp_capabilities_admin_pages()
{
    return ['pp-capabilities-roles'];
}

function check($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: $message\n";
}

function writeFixture($file, $contents)
{
    file_put_contents($file, $contents);
    $GLOBALS['fixtureFiles'][] = $file;
}

require $root . '/lib/vendor/publishpress/bundled-translations/core/HubTranslationNotice.php';
require $root . '/lib/vendor/publishpress/bundled-translations/core/BundledTranslations.php';

try {
    if ($isPro) {
        require $root . '/includes-pro/language-packs.php';
        if ($withStandaloneFree) {
            $freeSource = getenv('CAPABILITIES_FREE_SOURCE');
            if (!$freeSource || !is_file($freeSource . '/includes/language-packs.php')) {
                throw new RuntimeException('Set CAPABILITIES_FREE_SOURCE for the combined Free/Pro scenario.');
            }
            require $freeSource . '/includes/language-packs.php';
            if (in_array('--free-first', $argv, true)) {
                pp_capabilities_init_translations();
                pp_capabilities_pro_init_translations();
            } else {
                pp_capabilities_pro_init_translations();
                pp_capabilities_init_translations();
            }
        } else {
            $freeSource = $root . '/lib/vendor/publishpress/publishpress-capabilities';
            $bundled = new PublishPress\BundledTranslations\BundledTranslations(
                'capability-manager-enhanced',
                $freeSource . '/languages',
                $freeSource . '/capsman-enhanced.php'
            );
            $bundled->init();
            pp_capabilities_pro_init_translations();
        }
        pp_capabilities_pro_init_translations();
    } else {
        require $root . '/includes/language-packs.php';
        pp_capabilities_init_translations();
        pp_capabilities_init_translations();
    }

    $domains = apply_filters('publishpress_hub_language_pack_domains', []);
    $expected = $isPro ? 2 : 1;
    check(count($domains) === $expected, 'Each active text domain is registered once');
    $expectedRepositories = [
        'capability-manager-enhanced' => 'publishpress-capabilities',
        'capabilities-pro' => 'publishpress-capabilities-pro',
    ];

    foreach ($domains as $entry) {
        $domain = $entry['domain'];
        check($entry['repository'] === $expectedRepositories[$domain], 'Correct repository for ' . $domain);
        check($entry['version'] === '2.55.0', 'Actual installed release version for ' . $domain);
        $base = $packDirectory . '/' . $domain . '-pt_BR';
        writeFixture($base . '.release', '2.55.0');
        foreach (['mo', 'l10n.php', 'json'] as $extension) {
            writeFixture($base . '.' . $extension, 'fixture catalog');
        }

        foreach (['mo', 'l10n.php'] as $extension) {
            $source = WP_LANG_DIR . '/plugins/' . $domain . '-pt_BR.' . $extension;
            check(
                apply_filters('load_translation_file', $source, $domain, 'pt_BR') === $base . '.' . $extension,
                'Hub ' . $extension . ' overrides the bundled catalog without Hub active'
            );
            $loco = WP_LANG_DIR . '/loco/plugins/' . $domain . '-pt_BR.' . $extension;
            check(
                apply_filters('load_translation_file', $loco, $domain, 'pt_BR') === $loco,
                'Loco ' . $extension . ' override remains untouched'
            );
        }
        check(
            apply_filters('load_script_translation_file', false, 'fixture', $domain) === $base . '.json',
            'Hub JSON loads when WordPress has no script catalog'
        );
        $loco = WP_LANG_DIR . '/loco/plugins/' . $domain . '-pt_BR.json';
        check(
            apply_filters('load_script_translation_file', $loco, 'fixture', $domain) === $loco,
            'Loco JSON override remains untouched'
        );

        file_put_contents($base . '.release', '2.54.0');
        $source = WP_LANG_DIR . '/plugins/' . $domain . '-pt_BR.mo';
        check(
            apply_filters('load_translation_file', $source, $domain, 'pt_BR') !== $base . '.mo',
            'A stale release pack is not loaded for the new plugin version'
        );
        file_put_contents($base . '.release', '2.55.0');
        unlink($base . '.mo');
        check(
            apply_filters('load_translation_file', $source, $domain, 'pt_BR') !== $base . '.mo',
            'Missing downloaded catalog keeps the existing fallback'
        );
        $uploadsError = 'unavailable';
        check(
            apply_filters('load_script_translation_file', false, 'fixture', $domain) !== $base . '.json',
            'Upload directory errors keep the existing fallback'
        );
        $uploadsError = false;

        check(
            apply_filters('publishpress_bundled_translations_hub_notice_enabled', false, $domain) === true,
            'Existing translation notice remains enabled'
        );
        $plugin_page = 'pp-capabilities-roles';
        $screens = apply_filters('publishpress_bundled_translations_hub_notice_screens', [], $domain);
        check(in_array('users_page_pp-capabilities-roles-network', $screens, true), 'Notice remains scoped to the plugin screen');
    }

    check(
        apply_filters('load_translation_file', '/other.mo', 'another-domain', 'pt_BR') === '/other.mo',
        'Other text domains are untouched'
    );
    $loader = new PP_Capabilities_Hub_Language_Packs('capabilities-pro', 'publishpress-capabilities-pro', 'PUBLISHPRESS_CAPS_PRO_VERSION');
    check($loader->translationFile('/original.mo', 'capabilities-pro', '../pt_BR') === '/original.mo', 'Unsafe locales are rejected');
    check(
        isset(apply_filters('publishpress_hub_language_pack_domains', ['other' => ['domain' => 'other']])['other']),
        'Other plugin registrations are preserved'
    );
} finally {
    foreach (array_unique($fixtureFiles) as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    rmdir($packDirectory);
    rmdir(dirname($packDirectory));
    rmdir($fixtureRoot);
}
