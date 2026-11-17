---
title: "How to Add Capabilities Using Code"
order: 18
doc_category: cme
---

# How to Add Capabilities Using Code

[PublishPress Capabilities](https://publishpress.com/links/capabilities) allow developers to hook into the plugin and add your own capabilities to the “Capabilities” screen.

If you are a plugin or theme developer, you can use a function like this example:

```
/**
 * PublishPress Capabilities Support
 *
 * @param array $plugin_caps
 *
 * @return array
 */
add_filter('cme_plugin_capabilities', function ($pluginCaps) {
    $pluginCaps['Plugin Name'] = plugin_capabilities_lists_function();

    return $pluginCaps;
});
```

[PublishPress Capabilities](https://publishpress.com/links/capabilities) is hosted on Github, and there plenty of examples to check. Here are some of the examples:

- [Fluent Forms](https://github.com/publishpress/PublishPress-Capabilities/blob/development/includes/plugin-capabilities/fluentform.php)
- [bbPress](https://github.com/publishpress/PublishPress-Capabilities/blob/development/includes/plugin-capabilities/bbpress.php)
- [Wordfence](https://github.com/publishpress/PublishPress-Capabilities/blob/development/includes/plugin-capabilities/wordfence.php)
