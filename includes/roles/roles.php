<div class="wrap publishpress-caps-manage pressshack-admin-wrapper pp-capability-roles-wrapper">

    <?php
    if (isset($_GET['add']) && $_GET['add'] === 'new_item') {
        pp_capabilities_roles()->admin->get_roles_edit_ui();
     }else{ ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php esc_html_e('Roles', 'capability-manager-enhanced') ?> </h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=pp-capabilities-roles&add=new_item')); ?>" class="page-title-action">
            <?php esc_html_e('Add New', 'capability-manager-enhanced'); ?>
        </a>
        <?php
        if (isset($_REQUEST['s']) && $search_str = esc_attr(sanitize_text_field(wp_unslash($_REQUEST['s'])))) {
            /* translators: %s: search keywords */
            printf(' <span class="subtitle">' . esc_html__('Search results for %s') . '</span>', '&#8220;' . esc_html($search_str) . '&#8221;');
        }

        //the roles table instance
        $table = pp_capabilities_roles()->admin->get_roles_list_table();
        $table->prepare_items();
        pp_capabilities_roles()->notify->display();
        ?>
        <form action="" method="post">
            <hr class="wp-header-end">
            <div id="ajax-response"></div>

            <div id="col-container" class="wp-clearfix">
                <div class="col-wrap">
                    <?php $table->display(); //Display the table ?>
                </div>
            </div>
        </form>
    </div>
    <?php } ?>


    <?php if (!defined('PUBLISHPRESS_CAPS_PRO_VERSION') || get_option('cme_display_branding')) {
        cme_publishpressFooter();
    }
    ?>
</div>
<?php
