<?php

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('PP_Capabilities_Hub_Language_Packs', false)) {
    return;
}

/** Shared by the standalone and Pro distributions. */
class PP_Capabilities_Hub_Language_Packs
{
    private $domain;
    private $repository;
    private $versionConstant;

    public function __construct($domain, $repository, $versionConstant)
    {
        $this->domain = $domain;
        $this->repository = $repository;
        $this->versionConstant = $versionConstant;
    }

    public function register()
    {
        add_filter('publishpress_hub_language_pack_domains', [$this, 'registerDomain']);
        // Run after bundled-translations so downloaded catalogs take precedence.
        add_filter('load_translation_file', [$this, 'translationFile'], 20, 3);
        add_filter('load_script_translation_file', [$this, 'scriptTranslationFile'], 20, 3);
        add_filter('publishpress_bundled_translations_hub_notice_enabled', [$this, 'enableNotice'], 10, 2);
        add_filter('publishpress_bundled_translations_hub_notice_screens', [$this, 'noticeScreens'], 10, 2);
    }

    public function registerDomain($domains)
    {
        $domains = is_array($domains) ? $domains : [];
        if (defined($this->versionConstant)) {
            $domains[$this->domain] = [
                'domain' => $this->domain,
                'repository' => $this->repository,
                'version' => constant($this->versionConstant),
            ];
        }
        return $domains;
    }

    public function translationFile($file, $domain, $locale)
    {
        if (!is_string($file) || !preg_match('/\.(l10n\.php|mo)$/', $file, $matches)) {
            return $file;
        }
        return $this->preferDownloadedFile($file, $domain, $locale, $matches[1]);
    }

    public function scriptTranslationFile($file, $handle, $domain)
    {
        return $this->preferDownloadedFile($file, $domain, determine_locale(), 'json');
    }

    private function preferDownloadedFile($file, $domain, $locale, $extension)
    {
        if ($domain !== $this->domain || !defined($this->versionConstant)) {
            return $file;
        }
        if (is_string($file) && strpos(wp_normalize_path($file), '/loco/plugins/') !== false) {
            return $file;
        }
        if (!is_string($locale) || !preg_match('/^[A-Za-z0-9_-]+$/', $locale)) {
            return $file;
        }
        $uploads = wp_upload_dir(null, false);
        if (!is_array($uploads) || !empty($uploads['error']) || empty($uploads['basedir'])) {
            return $file;
        }
        $base = rtrim($uploads['basedir'], '/\\') . '/publishpress/language-packs/'
            . $this->domain . '-' . $locale;
        $marker = $base . '.release';
        if (!is_readable($marker) || trim((string) file_get_contents($marker)) !== constant($this->versionConstant)) {
            return $file;
        }
        $downloaded = $base . '.' . $extension;
        return is_readable($downloaded) ? $downloaded : $file;
    }

    public function enableNotice($enabled, $domain)
    {
        return $domain === $this->domain ? true : $enabled;
    }

    public function noticeScreens($screens, $domain)
    {
        global $plugin_page;

        if ($domain !== $this->domain || !function_exists('get_current_screen') || !function_exists('pp_capabilities_admin_pages')) {
            return $screens;
        }
        $screen = get_current_screen();
        $pages = array_merge(pp_capabilities_admin_pages(), ['pp-capabilities-user-view']);
        if (!$screen || !in_array($plugin_page, $pages, true)) {
            return $screens;
        }
        $screenId = preg_replace('/-(network|user)$/', '', $screen->id);
        $suffix = '_page_' . $plugin_page;
        if (substr($screenId, -strlen($suffix)) !== $suffix) {
            return $screens;
        }
        return array_values(array_unique(array_merge((array) $screens, [$screen->id])));
    }
}
