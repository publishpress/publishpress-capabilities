<?php
/**
 * PublishPress Capabilities [Free]
 *
 * UI output for per-user capabilities view.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap publishpress-caps-manage pressshack-admin-wrapper ppc-user-capabilities-wrap">
    <div class="ppc-user-capabilities-header">
        <h1><?php esc_html_e('User Capabilities', 'capability-manager-enhanced'); ?></h1>
        <a href="<?php echo esc_url($back_url); ?>" class="page-title-action"><?php esc_html_e('Back to Users', 'capability-manager-enhanced'); ?></a>
    </div>

    <p>
        <?php
        printf(
            esc_html__('Viewing effective capabilities for %s.', 'capability-manager-enhanced'),
            esc_html($selected_user->display_name)
        );
        ?>
    </p>

    <dl class="ppc-user-capabilities-meta">
        <div class="ppc-user-capabilities-meta-item">
            <dt><?php esc_html_e('Username', 'capability-manager-enhanced'); ?></dt>
            <dd><code><?php echo esc_html($selected_user->user_login); ?></code></dd>
        </div>

        <?php if (!empty($selected_user->user_email)) : ?>
            <div class="ppc-user-capabilities-meta-item">
                <dt><?php esc_html_e('Email', 'capability-manager-enhanced'); ?></dt>
                <dd><?php echo esc_html($selected_user->user_email); ?></dd>
            </div>
        <?php endif; ?>

        <div class="ppc-user-capabilities-meta-item">
            <dt><?php esc_html_e('Assigned Roles', 'capability-manager-enhanced'); ?></dt>
            <dd><?php echo (int) count($assigned_roles); ?></dd>
        </div>

        <div class="ppc-user-capabilities-meta-item">
            <dt><?php esc_html_e('Granted Capabilities', 'capability-manager-enhanced'); ?></dt>
            <dd><?php echo (int) count($effective_granted_caps); ?></dd>
        </div>
    </dl>

    <div class="ppc-user-capabilities-grid">
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php esc_html_e('Assigned Roles', 'capability-manager-enhanced'); ?></h2>
            </div>
            <div class="inside">
                <?php if (empty($assigned_roles)) : ?>
                    <p><?php esc_html_e('This user has no assigned roles.', 'capability-manager-enhanced'); ?></p>
                <?php else : ?>
                    <ul>
                        <?php foreach ($assigned_roles as $role_data) : ?>
                            <li>
                                <?php if (!empty($role_data['url'])) : ?>
                                    <a href="<?php echo esc_url($role_data['url']); ?>"><?php echo esc_html($role_data['label']); ?></a>
                                <?php else : ?>
                                    <?php echo esc_html($role_data['label']); ?>
                                <?php endif; ?>
                                <code><?php echo esc_html($role_data['slug']); ?></code>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="postbox">
            <div class="postbox-header">
                <h2><?php printf(esc_html__('Granted Capabilities (%d)', 'capability-manager-enhanced'), (int) count($effective_granted_caps)); ?></h2>
            </div>
            <div class="inside">
                <p class="ppc-user-cap-note"><?php esc_html_e('These are the capabilities WordPress resolves for this user after role assignments and any direct overrides.', 'capability-manager-enhanced'); ?></p>
                <?php if (empty($effective_granted_caps)) : ?>
                    <p><?php esc_html_e('This user has no granted capabilities.', 'capability-manager-enhanced'); ?></p>
                <?php else : ?>
                    <ul class="ppc-user-cap-list">
                        <?php foreach ($effective_granted_caps as $capability) : ?>
                            <li><code><?php echo esc_html($capability); ?></code></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($effective_denied_caps)) : ?>
            <div class="postbox">
                <div class="postbox-header">
                    <h2><?php printf(esc_html__('Denied Capabilities (%d)', 'capability-manager-enhanced'), (int) count($effective_denied_caps)); ?></h2>
                </div>
                <div class="inside">
                    <ul class="ppc-user-cap-list">
                        <?php foreach ($effective_denied_caps as $capability) : ?>
                            <li><code><?php echo esc_html($capability); ?></code></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($direct_granted_caps) || !empty($direct_denied_caps)) : ?>
            <div class="postbox">
                <div class="postbox-header">
                    <h2><?php esc_html_e('Direct User Overrides', 'capability-manager-enhanced'); ?></h2>
                </div>
                <div class="inside">
                    <p class="ppc-user-cap-note"><?php esc_html_e('These entries are stored directly on the user account instead of coming from a role.', 'capability-manager-enhanced'); ?></p>

                    <?php if (!empty($direct_granted_caps)) : ?>
                        <h3 class="ppc-user-cap-section-title"><?php esc_html_e('Granted', 'capability-manager-enhanced'); ?></h3>
                        <ul class="ppc-user-cap-list">
                            <?php foreach ($direct_granted_caps as $capability) : ?>
                                <li><code><?php echo esc_html($capability); ?></code></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if (!empty($direct_denied_caps)) : ?>
                        <h3 class="ppc-user-cap-section-title"><?php esc_html_e('Denied', 'capability-manager-enhanced'); ?></h3>
                        <ul class="ppc-user-cap-list">
                            <?php foreach ($direct_denied_caps as $capability) : ?>
                                <li><code><?php echo esc_html($capability); ?></code></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($publishpress_permissions['available'])) : ?>
            <div class="postbox">
                <div class="postbox-header">
                    <h2><?php esc_html_e('PublishPress Permissions', 'capability-manager-enhanced'); ?></h2>
                </div>
                <div class="inside">
                    <p class="ppc-user-cap-note">
                        <?php esc_html_e('Direct Permissions settings for this user. These are separate from WordPress role capabilities.', 'capability-manager-enhanced'); ?>
                    </p>

                    <h3 class="ppc-user-cap-section-title">
                        <?php
                        printf(
                            esc_html__('Extra Roles (%d)', 'capability-manager-enhanced'),
                            (int) $publishpress_permissions['roles_total']
                        );
                        ?>
                    </h3>

                    <?php if (empty($publishpress_permissions['roles'])) : ?>
                        <p><?php esc_html_e('This user has no direct extra roles.', 'capability-manager-enhanced'); ?></p>
                    <?php else : ?>
                        <ul class="ppc-user-permissions-list ppc-user-permissions-scroll">
                            <?php foreach ($publishpress_permissions['roles'] as $role_data) : ?>
                                <li>
                                    <?php echo esc_html($role_data['label']); ?>
                                    <code><?php echo esc_html($role_data['slug']); ?></code>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <h3 class="ppc-user-cap-section-title">
                        <?php
                        printf(
                            esc_html__('Specific Permissions (%d)', 'capability-manager-enhanced'),
                            (int) $publishpress_permissions['exceptions_total']
                        );
                        ?>
                    </h3>

                    <?php if (empty($publishpress_permissions['exceptions'])) : ?>
                        <p><?php esc_html_e('This user has no direct specific permissions.', 'capability-manager-enhanced'); ?></p>
                    <?php else : ?>
                        <ul class="ppc-user-permissions-list ppc-user-permissions-scroll">
                            <?php foreach ($publishpress_permissions['exceptions'] as $exception) : ?>
                                <li>
                                    <strong><?php echo esc_html($exception['operation']); ?></strong>
                                    <?php echo esc_html($exception['modification']); ?>
                                    <span class="ppc-user-permissions-scope"><?php echo esc_html($exception['scope']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if ($publishpress_permissions['exceptions_total'] > count($publishpress_permissions['exceptions'])) : ?>
                            <p class="ppc-user-cap-note">
                                <?php
                                printf(
                                    esc_html__('Showing the first 50 of %d permissions. Use the Permissions editor to view the full list.', 'capability-manager-enhanced'),
                                    (int) $publishpress_permissions['exceptions_total']
                                );
                                ?>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!empty($publishpress_permissions['roles_edit_url']) || !empty($publishpress_permissions['exceptions_edit_url'])) : ?>
                        <div class="ppc-user-permissions-actions">
                            <?php if (!empty($publishpress_permissions['roles_edit_url'])) : ?>
                                <a href="<?php echo esc_url($publishpress_permissions['roles_edit_url']); ?>" class="button">
                                    <?php esc_html_e('Edit Extra Roles', 'capability-manager-enhanced'); ?>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($publishpress_permissions['exceptions_edit_url'])) : ?>
                                <a href="<?php echo esc_url($publishpress_permissions['exceptions_edit_url']); ?>" class="button">
                                    <?php esc_html_e('Edit Specific Permissions', 'capability-manager-enhanced'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
