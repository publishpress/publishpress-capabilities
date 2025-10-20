The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

= 2.21.0 = - 23 September 2025 =
  * Fixed : WooCommerce sub-menus restriction bugs, #1447
  * Fixed : Unable to Restrict access to Submenus under Settings menu, #1456
  * Update : Disable Admin Notices by default, #1444
  * Fixed : Pro: License key input limits key length, #1440
  * Compat : PublishPress Revisions: Support Approve Revision capabilities, #1445
  * Compat : Support management of Approve Revision capabilities, #1446
  * Update : Translation Updates Capabilities 2.20.0-ES-FR-IT-BR, #1435

= 2.20.0 = - 25 July 2025 =
  * Fixed : Error Message on Admin Features Restrictions screen, #1415
  * Update : Key links on Plugins screen, #1403
  * Update : Text changes for tooltips, #1408
  * Update : More space for Editor Features "Element IDs or Classes" box, #1398
  * Update : Update lib-status-capabilities to v1.1.2, #1412
  * Update : Status Edit - capability column headers on Post Access tab not clickable #1402
  * Update : Add a "Pro" nudge to "Selected Pages", #1407
  * Update : Add Pro icons to "Hide CSS Elements" and "Block by URL", #1405

= 2.19.2 = - 12 May 2025 =
  * Fixed : WooCommerce System Report button is hidden due to Admin Notices inclusion, #1372
  * Update : Update admin-notices.css to match Future's notifications style and WordPress' native layout, #1373
  * Fixed : Admin Notices always has a black background, #1359
  * Fixed : Cannot assign manage_post_tags capability unless Permissions is active, #1375
  * Fixed : Visibility Statuses column header for Set capability is misaligned, #1378
  * Fixed : Mistake in german translation, #1365
  * Fixed : Jumping tabs in Admin Notices, #1361
  * Update : Update the plugin description, #1369
  * Update : Capabilities 2.19.1 Translation Updates ES-FR-IT, #1360

= 2.19.1 = - 25 Feb 2025 =
  * Fixed : Conflict with Gravity Forms, #1347
  * Update : Hidden notice text, #1329
  * Fixed : Custom submenu not showing after created, #1345
  * Fixed : Submenu overwrite when create 2 submenus, #1346
  * Update : Translation Updates Capabilities 2.19 ES-FR-IT, #1343

= 2.19.0 = - 13 Feb 2025 =
  * Feature : Ability to hide admin notices, #135
  * Fixed : Broken layout for Admin Features in Free version, #1308
  * Fixed : ACF and Custom Post Type UI submenu missing after reorder, #1324
  * Update : Update admin menus promo text, #1309
  * Update : Update tooltips on Capabilities screen, #1310
  * Update : Translation Updates Capabilities 2.18.2, #1306

= 2.18.2 = - 30 Jan 2025 =
  * Update : Add Admin Menus sidebar settings to Show/Hide menu slugs, #1274
  * Update : Add Admin Menus sidebar settings to Show/Hide sub-menus, #1258
  * Update : Make sure Admin Menu slug display represent their url path in WordPress menu, #1302
  * Update : Add placeholder text for Admin Menus with empty label text, #1300
  * Fixed : Roles bulk delete not working, #1259
  * Update : Add Sidebar tabs for Admin Features, #1126
  * Update : Rename the "Reading" tab to "Private", #1295
  * Update : Improve tooltips over table headers, #1296
  * Update : Add examples for Editor Features, #1127
  * Update : Only enforce pp_administer_content when Permission plugin is active, #1301
  * Fixed : Menu conflict with Learndash on a multisite, #1286
  * Update : Replace "Update Role" with "Save Changes", #1283
  * Fixed : Save Changes button in wrong place, #1303
  * Fixed : PHP Fatal error: Uncaught TypeError: Illegal offset type in isset or empty, #1290
  * Update : Translation Updates for Capabilities 2.18.0, #1278

= 2.18.0 = - 09 Jan 2025 =
  * Update : Add a button to reset Admin Menus Order and Name to their old names and order, #1268
  * Fixed : Detailed Taxonomy Capabilities for Custom Taxonomies Not Granted After Page Reload, #1253
  * Update : "Registration Redirects" feature redirects admin when new users are added from the admin area, #1269
  * Fixed : Admin Menus compatibility issue with TaxoPress Pro menus, #1252
  * Fixed : Issue with Capabilities menu order, #1247
  * Fixed : Issue with WooCommerce menus, #1255
  * Fixed : Missing "Orders" on WooCommerce area in "Admin Menus", #1254
  * Fixed : Tooltip goes behind the sidebar menu, #1260
  * Update : Change text to "Menu Link", #1251
  * Update : Added a question mark(?) for deleting new admin menu links, #1257
  * Update : Brazil Translation Capabilities, #1249
  * Update : Translation Updates Capabilities 2.17.0, #1246

= 2.17.0 = - 17 Dec 2024 =
  * Feature : Create a new "Redirects" screen, #1201
  * Update : Allow admin to add new menu, sub menu and separators, #1195
  * Fixed : Support for custom icons in Admin Menus, #1225
  * Update : Update WSForm capabilities, #1215
  * Update : Add a heading for custom CSS in Editor Features, #1224
  * Update : Small text change for Editor Features, #1223
  * Fixed : Cannot access offset of type string on string Profile Feature, #1227
  * Fixed : Cannot select role in dropdown when $_POST has values, #1231
  * Fixed : ErrorException: Warning: foreach() argument must be of type array|object, string given, #1233
  * Fixed : "Undefined array key" and "Attempt to read property on null" errors, #1235
  * Compat : Statuses Pro (custom Revision statuses)

= 2.16.0 - 26 Nov 2024 =
  * Fixed : Admin menu with count losing their counts html part after renaming menu, #1219
  * Fixed : Cannot Rename Menu on Omnisend Plugin, #1217
  * Fixed : Editor Feature not working Permalink and Template, #1198
  * Update : Support for custom menu items, #183
  * Update : Allow users in Admin Menus to see the required capabilities, #1200
  * Fixed : Admin menu icon not working in Import / Export feature, #1205
  * Update : Change "Admin Menu Restrictions" to "Admin Menus", #1212
  * Update : Text update for dashboard, #1207
  * Fixed : PHP Warning: Undefined array key 2, #1197
  * Update : Brazil Translations for Capabilities Pro, #1213
  * Update : Capabilities Translation Updates V 2.15.0, #1210

= 2.15.0 - 13 Nov 2024 =
  * Feature : Reorder and rename admin menus for roles [PRO], #254
  * Fixed : Fatal error: Uncaught Error: Undefined constant "MULTISITE", #1187
  * Update : German translation Update, #1192
  * Update : Brazil Translation for PRO, #1191
  * Update : Capabilities FREE Translation Updates v. 2.14.0, #1186

= 2.14.0 - 22 Oct 2024 =
  * Update : Editor feature not working due to duplicate inline css, #1148
  * Fixed : Editor Features not working for some items, #1145
  * Update : Redirect new users to the "Dashboard" screen, #1150
  * Fixed : Changing a capability from disabled to enabled not working when clicking the label, #1173
  * Update : Move multisite settings to the sidebar, #1146
  * Fixed : PHP warning about status capabilities, #1143
  * Fixed : Unnecesary  database updates on init, #1139
  * Fixed : Double queries in admin pages, #1140
  * Update : Update the WooCommerce capabilties, #1135
  * Update : Add a mesage if the "Additional" tab is empty, #1137
  * Fixed : Warning: Undefined array key "user-testing", #1174
  * Update : Show Wordfence capabilities tab for both Wordfence main and Login Security plugin, #1136
  * Update : New filter "add_filter('pp_capabilities_frontend_feature_cache', '__return_false);" to disable Frontend feature data cache, #1152
  * Update : Add a hook on after user testing action, #1160
  * Fixed : pp_capabilities_get_post_type returns wrong post type, #1161
  * Fixed : Editor Features custom item adding slashes to double quote(""), #689
  * Fixed : Compatibility issue with Justified Image Grid, #1165
  * Update : Permissions Compat: Allow direct assignment of manage_post_tags if unique taxonomy caps enabled for Tags, #1163
  * Update : Capabilities screen: "Listing" tab should not require Pro version of Permissions, #1158
  * Update : Translation Updates Capanilities v.2.13.0, #1134

= 2.13.0 - 12 Feb 2024 =
  * Update : Add edit to features custom items, #996
  * Update : Option to add Test User to the admin toolbar, #1047
  * Update : Add demo content for Frontend Features and Admin Feature, #871
  * Fixed : Broken tooltip in Admin Menus, #1125
  * Fixed : Capabilities Media tab not working in french, #1119
  * Fixed : Frontend feature not working in french, #1118
  * Update : Standard approach to all UI elements in Capabilities, #1029
  * Update : Match Custom Visibility to other statuses, #1061
  * Update : Match the Capabilities Custom Statuses heading to other tabs, #1058
  * Update : Hide Document Overview using Editor Feature, #1040
  * Fixed : Read Capability not working, #1039

= 2.12.2 - 6 Feb 2024 =
  * Fixed : French translation caused "Upgrade to Pro" banner to be rendered incorrectly

= 2.12.1 - 30 Jan 2024 =
  * Change : Improved use of screen space on Capabilties tab panels
  * Change : In mobile view, scroll to panel for clicked Capabilities tab
  * Change : Display right sidebar metaboxes inside tab panel, but only where they're relevant
  * Change : In Add Capability sidebar metabox, mention that new capabilities will show up on Additional tab
  * Change : Bulk Check All box on Editing, Deletion, Listing, Reading, Taxonomies tabs
  * Change : Use 90% opacity for Capabilities tool tip
  * Change : Don't display "This capability is" tool tip if capability name is already displayed next to checkbox
  * Change : Cleaner styling for capabilities assigned implicitly by Permission Group
  * Change : Update Pro promo sidebar on Capabilities Dashboard to mention Custom Statuses, Custom Visibility
  * Lang: Some tab captions on Capabilities screen were not translated
  * Lang: Default WordPress strings (Edit, Settings, etc.) used by plugin were made to rely on plugin language files

= 2.11.1 - 04 Jan 2024 =
  * Fixed: Inconsistencies with language files, #311

= 2.10.3 - 12 Dec 2023 =
  * Fixed: Nav menu not working in latest WordPress version for FSE theme, #1048
  * Update: Add ability to define test user cookie name, define('PPC_TEST_USER_COOKIE_NAME', 'replace_this_with_your_cookie_name'); #1033
  * Fixed: Last update breaking nav-menus.php for some sites, #1037
  * Fixed: PHP Fatal error: Uncaught Error: Call to undefined function pp_get_enabled_types() on capabilities screen, #1045
  * Update: Add support for Squirrly SEO (Newton) plugin capabilities tab, #1044
  * Update: Add support for AMP plugin capabilities tab, #1043
  * Update: Add support for SEOPress plugin capabilities tab, #1042
  * Update: Add support for MailOptin - Lite plugin capabilities tab, #1041
  * Update: Capabilities Translation Updates November 2023, #1036

= 2.10.2 - 15 Nov 2023 =
  * Update: Add view and edit to features custom items, #741
  * Update: Capabilities screen: distinguish Navigation Block Menus from legacy Nav Menus, #888
  * Update: Hotfix use create posts capability caption, #960
  * Update: Return an empty string instead of false in the admin_footer_text filter hook callback, #961
  * Update: Text update for "Listing" tab, #811
  * Update: Hide "Listing" tab with Permissions Free, #812
  * Update: Add settings to choose Role Login Redirect Referrer, #933
  * Update: Move items from Roles Column to the Roles screen, #817
  * Update: Add visual indicator on Appearance > Menu items when a nav menu item is hidden for roles, #844
  * Update: Add support for BetterDocs plugin capabilities tab, #986
  * Update: Add support for GravityView plugin capabilities tab, #985
  * Update: Add support for Gravity Forms plugin capabilities tab, #984
  * Update: Add support for NextGEN Gallery plugin capabilities tab, #983
  * Update: Add support for BackWPup plugin capabilities tab, #982
  * Update: Add support for Forminator plugin capabilities tab, #981
  * Update: Add support for LearnDash LMS plugin capabilities tab, #980
  * Update: Add support for MailPoet plugin capabilities tab, #979
  * Update: Add support for Fluent Forms plugin capabilities tab, #978
  * Update: Add support for All in One SEO plugin capabilities tab, #976
  * Update: Add support for Smash Balloon Instagram Feed plugin capabilities tab, #977
  * Update: Add support for Site Kit by Google plugin capabilities tab, #975
  * Update: Add support for Wordfence Security Duplicate Post plugin capabilities tab, #973
  * Update: Add support for Smart Slider 3 plugin capabilities tab, #972
  * Update: Add support for Loco Translate plugin capabilities tab, #971
  * Update: Add support for Rank Math SEO plugin capabilities tab, #970
  * Update: Add support for Query Monitor plugin capabilities tab, #969
  * Update: Add support for Strong Testimonials plugin capabilities tab, #968
  * Update: Add support for Download Monitor plugin capabilities tab, #967
  * Update: Add support for Yoast SEO plugin capabilities tab, #966
  * Update: Add support for Formidable Forms plugin capabilities tab, #958
  * Update: Add support for Give - Donation plugin capabilities tab, #957
  * Update: Add support for BuddyPress plugin capabilities tab, #947
  * Update: Add support for bbPress plugin capabilities tab, #946
  * Update: Add support for Sunshine Photo Cart plugin capabilities tab, #943
  * Update: Capabilities Free Translation Updates October 2023, #934

= 2.10.1 - 23 Oct 2023 =
  * Fixed: Login redirect not working, #843
  * Fixed: Redirect user loop, #849
  * Fixed: Alignment for menu items, #780
  * Update: Update Capabilities old-fashioned tooltips, #818
  * Fixed: Negate could not be removed, #873
  * Update: Add a tooltip for manage_post_tags, #919
  * Update: Remove add_users from the basic Capabilities, install #918
  * Update: Update Body Class description, #893
  * Fixed: Profile Features conflict with Advanced Custom Fields: Extended plugin, #910
  * Fixed: Function pp_capabilities_sub_menu_lists has wrong return type in the function docs, #848
  * Fixed: Admin loosing Access to Capabilities after role reset, #834

= 2.10.0 - 6 Sep 2023 =
  * Changed: Replaced Pimple library with a prefixed version of the library to avoid conflicts with other plugins;
  * Changed: Replaced Psr/Container library with a prefixed version of the library to avoid conflicts with other plugins;
  * Changed: Change min PHP version to 7.2.5. If not compatible, the plugin will not execute;
  * Changed: Change min WP version to 5.5. If not compatible, the plugin will not execute;
  * Changed: Updated internal libraries to latest versions;
  * Changed: Refactor some occurrences of "plugins_loaded" replacing it by a new action: "publishpress_capabilities_loaded" which runs after the requirements and libraries are loaded, but before the plugin is initialized;

= 2.9.1 - 1 Aug 2023 =
  * Fixed : Fatal error: Uncaught Error: Call to a member function has_cap() on null in Installer class, #880

= 2.9.0 - 31 Jul 2023 =
  * Feature : Allow people to target CSS to user roles (Frontend Features), #4
  * Fixed : Error on Backup > Reset Roles, #856

= 2.8.1 - 17 May 2023 =
  * Fixed : Undefined variable $cap_name, #822
  * Fixed : Full Site Editing admin menus issue with custom link, #807
  * Fixed : Profile Features table safari styles fix, #770
  * Fixed : Update dashboard feature texts, #820
  * Fixed : Editor Feature Options not hiding, #805

= 2.8.0 - 11 May 2023 =
  * Feature : Custom capabilities for each feature/menu, #727
  * Feature : Allow users to disable some features [Dashboard Menu], #491
  * Update : Remove "Show Classic Editor Controls" on Editor Features screen, #797
  * Update : Update Tooltip message for media capabilities, #796
  * Update : Add a Promo sidebar, #768

= 2.7.1 - 20 Apr 2023 =
  * Feature : Nav Menus Block Navigation/FSE theme support, #710
  * Update : Capability Sidebar Update #719
  * Feature : Add multisite capabilities tab, #737
  * Feature : Add tooltips to explain capabilities, #734
  * Feature : Single checkbox to block Dashboard access, #693
  * Update : Add "Toggle All" option in Admin Features, #694
  * Update : Add "Profile Features" column to "Roles", #740
  * Update : Update documentation links, #776
  * Update : Profile Features text update, #773
  * Update : Add an explanation of each screen, #691
  * Fixed : It's not possible to hide "sticky option" using Editor Feature, #724
  * Fixed : Taxonomies Delete title missing for disabled checkbox, #726

= 2.7.0 - 27 Feb 2023 =
  * Feature : Allow admins to customize the "Profile" screen for users (Profile Features), #271
  * Update : Make "Nav Menus" available in Free version, #606
  * Update : Add copy & revise capabilities when Revisions activated, #596
  * Update : Implement UI friendly checkbox for shared capabilities, #686
  * Update : Add required capability for WooCommerce admin restrictions, #687
  * Update : Ability to disable multiple roles on user edit screen, #622
  * Fixed : Custom item did not included in export/import, #631
  * Fixed : Template and Permalink are not hidden, #650
  * Fixed : Text error if no "read" capability, #700
  * Fixed : Application Timeout error in Capabilities plugin, #683
  * Update : Remove Note from plugin screens, #614
  * Fixed : Inconsistent right sidebar, #690
  * Update : Capabilities FR-IT Translation Updates 2023, #652

= 2.6.1 - 08 Dec 2022 =
  * Update : Allow users to block some roles from "User Testing", #621
  * Update : Allow admins to test users from the user profile page, #626
  * Fixed : Block URL not working, #629
  * Update : Block change of user level for Administrators, #628
  * Update : UI Consistency: vertical alignment of Usage Keys, Roles screen search box, #617
  * Fixed : Unexpected placeholder %1 warning, #624
  * Update : Change "PublishPress" to "PublishPress Planner" in Capabiliites, #638
  * Update : FREE Capabilities ES-FR-IT Translation Updates October 27, #620

= 2.6.0 - 25 Oct 2022 =
  * Added : Add a way for admins to test user accounts #57
  * Fixed : Multi-select JS fails to load on Profile Edit Screen #576
  * Update : Adding an explanation for Checkmark / empty / X #578
  * Update : Add text description to Settings #573
  * Fixed : Plugin translation ignores user's language setting #580
  * Fixed : Illegal string offset 'administrator' & Array to string conversion Warning #589
  * Update : Support for the "Templates" metabox #251
  * Fixed : Some CPT is missing from Editor Features #582
  * Update : Capabilities-FR-IT-TranslationUpdate-September2022 #577

= 2.5.2 - 04 Oct 2022 =
  * Update : Changes to Import/Export encoding method

= 2.5.1 - 13 Sep 2022 =
  * Added : Ability to block user login by role #510
  * Update : Add disable WooCommerce admin restrictions in role settings #549
  * Update : Hide taxonomy screen option for editor features #554
  * Update : Hide metabox screen option for editor features #556
  * Fixed : It's possible to access customize page even after blocking with admin menu #559
  * Fixed : Uncaught error: Illegal offset type in isset or empty #564
  * Update : Translation Note Suggestion for "Editor" #567
  * Update : Capability-FR-IT-Translation_updates-August15_2022 #551

= 2.5.0 - 11 Aug 2022 =
  * Update : Admin Features UI design consistency #466
  * Fixed : Support WordPress API for Editor features Classic Editor disable / enable #531
  * Fixed : Woocommerce order metabox Illegal offset type in isset or empty warning #538
  * Added : Added woocommerce coupon description to editor features #299
  * Update : ES-FR-IT-Capability-Translations-Update-August2022 #540

= 2.4.4 - 02 Aug 2022 =
  * Added : Add "list" capabilities to display #206
  * Added : Add a Settings screen to Free version #520
  * Added : Add settings to allow users to select multiple roles when creating users #462
  * Fixed : Multiple role when creating user doesn’t work #515
  * Fixed : Admin Menus issue with Yoast #493
  * Fixed : It's possible to get lockout of admin menus when all items are checked #527
  * Update : Limit "Control Custom Statuses" option to settings screens alone #528
  * Update : French and Italian Translations updates #524
  * Fixed : PHP Warning on viewing Admin Menus #525

= 2.4.3 - 12 Jul 2022 =
  * Fixed : Capabilities conflict with Advanced Custom Fields #494
  * Fixed : Issue with editor features when options is not array #495
  * Update : Add checkbox for the "Allowed Editors" feature #498
  * Update : Add support for TaxoPress on the Capabilities screen #500
  * Update : Use 3 clicks approach for all boxes in Capabilities #497
  * Fixed : Uncaught TypeError: in_array(): Argument #2 ($haystack) must be of type array, null given. #513
  * Fixed : Hide Invalid Capabilities if empty #490

= 2.4.2 - 14 Jun 2022 =
  * Fixed : Correct match of post types and Editor Features boxes #427
  * Update : Changes to user role selection UI and make role draggable for re-ordering #443
  * Added : Two new role tabs (Redirects and Editing) #403
  * Added : Redirect users to original page after login to role editor #301
  * Added : Redirect on login and logout to role editor #11
  * Fixed : Admin Menus issue with JetPack #381
  * Added : Hide the "add new block" button to editor feature #436
  * Added : Hide the Revisions box in Editor Features #428
  * Update : UI clean up for Backup screens #322
  * Update : Move the Taxonomy area to it's own tab #425
  * Added : Support for WPML capabilities #411
  * Added : Support for WS Form #305
  * Added : Support Gravity Forms support #306
  * Added : Add a new setting to disable Code Editor in Posts to role editor #298
  * Update : Remember last tab after updating role settings #445
  * Update : Add toggle all checkmarks options on the Capabilities screen #419
  * Update : More categorization for capabilities #303
  * Update : Force user roles to use specific editors in role settings #276
  * Added : Add a setting to show private taxonomies on the "Capabilities" screen #314
  * Added: Extend admin features "hide by css" to include plugin list #488

= 2.4.1 - 09 May 2022 =
  * Fixed : Small bug with Capabilities search #340
  * Update : Add sorting for more table columns in "Roles" #388
  * Fixed : Edit Role screen: Right sidebar links to Capabilities screen without role argument #407
  * Fixed : Admin Features issue with JetPack #412
  * Update : Stop Free and Pro from being enabled together #323
  * Update : Add PublishPress Building Package for Capabilities #400

= 2.4.0 - 28 Apr 2022 =
  * Fixed : Post title not working with editor features #370
  * Fixed : Issue with revision metabox and some plugin metabox in Editor features. #369
  * Fixed : Editor Features compability with taxonomies created by the "Toolset" plugin. #367
  * Fixed : Backup Features text missing some "s" #365
  * Update : Change import upload file delete to use WordPress function #364
  * Update : Make sure "Copy" feature for roles works with Editor Features and more #362
  * Update : Add short description for "Role Level" #361
  * Update : Make "Roles" into the top menu link #326
  * Update : Vertical tabs similar to the "Capabilities" for Editor Features #257
  * Fixed : Can't hide the "Profile" link with "Admin Menus" #337
  * Update : More Columns on Roles Screen #181

= 2.3.7 - 21 Apr 2022 =
  * Lang : Translations were not loaded on some sites

= 2.3.6 - 14 Apr 2022 =
  * Fixed : Non-administrators cannot access profile screen
  * Compat : WooCommerce - Shop Managers could not access Users
  * Compat : WooCommerce - Editor Feature restrictions did not hide Product Categories, Tags in Classic Editor

= 2.3.5 - 13 Apr 2022 =
  * Feature : Export / Import for new features
  * Change : Clarify captions on Roles, Backup screens
  * Fixed : Multisite: Don't apply Feature, Menu Restrictions to Super Administrators unless constant PP_CAPABILITIES_RESTRICT_SUPER_ADMIN is defined
  * Fixed : Capabilities could not be updated if third party code executes too early. Now support constant PP_CAPABILITIES_COMPAT_MODE to work around conflicts.
  * Fixed : Coding standards - WordPress VIP scan compliance improvements
  * Compat : LoginWP - custom redirect failed
  * Lang : New French, Italian and Spanish translations

= 2.3.4 - 26 Jan 2022 =
  * Compat : WordPress 5.9 - failure adding / editing posts under some Editor Features configurations (work around WP hooking late-defined function _disable_block_editor_for_navigation_post_type)

= 2.3.3 - 13 Jan 2022 =
  * Fixed : Capability names with dashes could not be added
  * Fixed : After role rename, title in dropdown does not refresh
  * Fixed : Input sanitization consistency
  * Fixed : Escape output variables
  * Lang : Spanish, French, Italian

= 2.3.2 - 8 Dec 2021 =
  * Feature : Filter Capabilities display by post type or text entry
  * Feature : Editor Features - Restrict editor elements for custom post types
  * Feature : Admin Features - Restrict Admin Bar or individual Admin Bar elements
  * Feature : Admin Features - More items available for restriction #240
  * Change : Admin Features - Captions use dashes, not numbers #229
  * Change : Capabilities screen - Tab for PublishPress Capabilities #220
  * Fixed : CSRF vulnerability

= 2.3.1 - 6 Dec 2021 =
  * Fixed : Security issue
  * Fixed : PHP Notice on Capabilities screen

= 2.3 - 28 Oct 2021 =
  * Change : Role Capabilities screen uses tabs
  * Feature : New "Admin Features" screen #200

= 2.2 - 26 Aug 2021 =
  * Feature : Retain last role selection for Capabilities, Editor Features screens
  * Perf : Sync role to all sites - Operation timed out on networks with ~100 sites
  * Fixed : Some security scans flagged an unused file in external library "chosen". That file (and other developer documentation files) has been removed

= 2.1 - 24 Jun 2021 =
  * Feature : Editor Features restriction (new screen to block editor elements per-role)
  * Fixed : If Media "Create" capability is selected / unselected by clicking Media caption or Create caption, the corresponding upload_files checkbox (in Other WP Core Capabilities section) is not toggled, leading to an apparant update failure
  * Fixed : If Media "Create" capability is negated or un-negated, the corresponding upload_files checkbox (in Other WP Core Capabilities section) is not toggled, leading to an apparant update failure
  * Fixed : PHP Warning if a role is stored without a valid capabilities array

= 2.0.2 - 6 May 2021 =
  * Feature : Multisite - "sync options to all sites" checkbox. Copies "use create_posts capability", Type-Specific Capabilities, Taxonomy-Specific Capabilities, Detailed Taxonomy Capabilities settings
  * Fixed : Multisite - "sync role to all sites" did not work if main site ID is not 1
  * Fixed : Fatal error on Capabilities screen if another plugin calls get_editable_roles() too early
  * Fixed : Add New User - couldn't display password entry
  * Compat : PublishPress - Authors without publish capability could directly publish on the Calendar screen
  * Change : Permissions - Hide / Unhide Role setting moved to Roles screen row actions

= 2.0 - 18 Feb 2021 =
  * Feature : Roles screen
  * Feature : Multiple role assignment on Add / Edit User screen
  * Lang : Fixed handling, activated partial translations in German, Italian, Russian, Spanish, Swedish, Belarusian, Catalan
  * Change : Capabilities screen - move role selector to top left, eliminate load button
  * Change : Capabilities screen - move some sidebar items to Settings screen
  * Change : Adjust some captions, variable names, more selective code execution
  * Compat : bbPress - Forum, Topic and Reply capabilities were not displayed in Editing Capabilities grid
  * Fixed : uneditable bbPress roles could be opened for editing (require Capabilities Pro)
  * Fixed : Invalid Capabilities - Brief explanatory caption; avoid false positives for post types with map_meta_cap disabled
  * Fixed : PHP warning for invalid foreach argument, on sites with no active_plugins option stored
  * Fixed : Backup > Restore - Negated capabilities were not displayed correctly in restore preview
  * Fixed : Backup > Restore - Clicking label for Initial Backup jumped selection to Last Manual Backup
  * Change : Backup > Restore - Preview displays "No changes" below role name where appropriate

= 1.10.1 - 8 Oct 2020 =
  * Fixed : Type-Specific Capabilities options included some non-public WordPress post types that don't support capability customization
  * Fixed : Review of role backup contents does not show name of current roles which would be removed by restoring backup

= 1.10 - 1 Oct 2020 =
  * Feature : Improved design and styling for Backup and Restore
  * Feature : Backup > Restore - filter to display only modified capabilities
  * Compat : Advanced Gutenberg - include AG Profile capabilities in Editing, Deletion, Reading capabilities grid
  * Fixed : Media Create / upload_files capability could not be removed from role
  * Fixed : Multisite - Incorrect menu display on sites where main site ID is not 1
  * Fixed : Language file load failure if plugin directory structure is non-standard

= 1.9.12 - 16 Jun 2020 =
  * Fixed : Fatal error due to missing vendor library folder

= 1.9.11 - 16 Jun 2020 =
  * Fixed : Upgrade menu links were not displayed

= 1.9.10 - 1 Jun 2020 =
  * Fixed : PublishPress Permissions - Type / Taxonomy settings incorrectly synchronized under some conditions

= 1.9.9 - 13 May 2020 =
  * Compat : PublishPress Permissions - "Type-Specific Capabilities" setting was not properly synchronized with Permissions > Settings > Core > Filtered Post Types

= 1.9.6 - 23 Apr 2020 =
  * Change : Add New Role retains capitalization as entered for role title (otherwise applies proper case)
  * Feature : Rename Role sidebar box on Capabilities screen
  * Fixed : Fatal error on plugin load if Administrator role does not exist
  * Compat : PublishPress Permissions - Post Type selections for "Type-Specific Capabilities" were not synchronized with PublishPress Permissions under some conditions

= 1.9.5 - 6 Apr 2020 =
  * Fixed : Fatal error loading Capabilities screen on a small percentage of installations
  * Compat : PublishPress Permissions - Post Type selections for "Type-Specific Capabilities" were not synchronized with PublishPress Permissions under some conditions

= 1.9.4 - 2 Apr 2020 =
  * Fixed : Fatal error loading Capabilities screen on a small percentage of installations
  * Fixed : Capabilities menu was displayed to non-Administrators with no items except "Upgrade to Pro"

= 1.9.3 - 17 Mar 2020 =
  * Fixed : Capabilities screen was not accessible to non-Administrators who have "manage_capabilities" capability
  * Fixed : Some functions were not accessible to network Super Administrators without a role on the site
  * Change : Clarify some messages for plugin access denial

= 1.9.2 - 16 Mar 2020 =
  * Feature : Auto-backup role and capabilities on each update (and on update to this version)
  * Fixed : First-time installation: Capabilities menu item not displayed until after Plugins or Users menu clicked
  * Change : Third Party Plugin Capabilities - always display checkboxes even if capabilities not present in Administrator role
  * Fixed : Plugin capability sections - pp_set_notification_channel and pp_manage_roles were included in both PublishPress and PublishPress Permissions sections
  * Fixed : Capability Negation (Denial) bulk unselect link was ambiguous due to missing strikethrough

= 1.9.1 - 16 Jan 2020 =
  * Fixed : Create Role, Copy Role, and Add Capability sidebar functions did not work with ENTER keypress (caused screen reload without applying operation)

= 1.9 - 9 Jan 2020 =
  * Change : Renamed to PublishPress Capabilities
  * Feature : Capabilities link on PublishPress > Roles row opens Role Capabilities screen
  * Feature : Role Capabilities screen links to PublishPress > Roles for member management
  * Fixed : Browser reload caused Role Capabilities screen to display default role
  * Fixed : Add Capability sidebar added custom capability to role immediately, but capability checkbox did not display as checked until reload
  * Fixed : Category Assign or Delete capabilities were not effective due to WordPress core forcing default capability requirement
  * Fixed : Term Assign or Delete capabilities were not effective due to WordPress core forcing default capability requirement
  * Fixed : Multisite - On sub-sites, Role Capabilities screen did not display PublishPress Capabilities section to Super Administrators who don't have a role on the site
  * Fixed : Role name captions on Role Capabilities and Backup Tool screens could not be translated
  * Fixed : Checkbox bulk selection on Role Capabilities screen was incorrect under some conditions
  * Change : Reinstate WordPress edit_published_posts workaround with correct status filtering behavior
  * Change : Apply workaround filters for WordPress edit_published_posts / publish_posts handling only for users who have edit_published_posts capability for current post type

= 1.8.1 - 25 Oct 2019 =
  * Fixed : Automatic publication of blank auto-drafts, WooCommerce posts save with incorrect post status (since 1.8)

= 1.8 - 24 Oct 2019 =
  * Feature : WooCommerce, PublishPress, PressPermit capabilities grouped in sections on role editor screen
  * Feature : Plugin API - plugins can hook into "cme_plugin_capabilities" filter to register their capabilities
  * Feature : Work around WordPress issue preventing users with edit_published_posts (but not publish_posts) capability from updating published posts (https://core.trac.wordpress.org/ticket/47443)
  * Feature : Work around WordPress issue allowing users with edit_published_posts (but not publish_posts) to unpublish published posts
  * Fixed : If a unique edit/delete capability is already defined, don't change the definition
  * Fixed : Removed add_users from the Core WordPress Capabilities section because it is was replaced by promote_users
  * Fixed : PHP Notices on Role Capabilities screen for undefined index, under some configurations
  * Fixed : HTML validation errors on Manage Capabilities screen
  * Fixed : PHP 5.x : Notice for undefined constant PHP_INT_MIN on wp-admin Posts / Pages listing
  * Change : Move Role Capabilities menu item to Permissions menu if PressPermit plugin is active (restoring previous behavior with Press Permit Core)
  * Change : Edit Roles link in CME row of Plugins list
  * Change : PublishPress icon, footer on Roles and Capabilities screen

= 1.7.5 - 24 May 2019 =
  * Fixed : Users' inclusion or non-inclusion in Authors dropdown was not updated based on role edit

= 1.7.4 - 1 May 2019 =
  * Fixed : On some sites, capabilities added dynamically by other code were forced into stored role definition (and could not be removed).
  * Fixed : Negative role capabilities could not be directly unset (had to be checked, saved, then unchecked).

= 1.7.3 - 9 Apr 2019 =
  * Fixed : Work around WP quirk of completely blocking admin page access for a post type if user lacks create capability for the post type and there are no other accessible items on the menu.
  * Fixed : PHP Notices on Roles and Capabilities screen for non-Administrator with WooCommerce active

= 1.7.2 - 3 Apr 2019 =
  * Compat : WooCommerce integration - Users lacking access to the "Add New Order" submenu could not access Posts, Pages, Products or any other Post Type listing. This occurred if "use create_posts" option enabled and user lacks the create capability for Orders.

= 1.7.1 - 29 Mar 2019 =
  * Fixed : Press Permit integration - cannot load Permissions > Role Capabilities with Press Permit Core < 2.7

= 1.7 - 28 Mar 2019 =
  * Feature : New right sidebar setting: "Type-Specific Capabilities" for selected post types (without activating Press Permit Core).
  * Feature : New right sidebar setting: "Taxonomy-Specific Capabilities" ensures a distinct manage capability for selected taxonomies
  * Feature : New right sidebar setting: "Detailed Taxonomy Capabilities" causes term assign, edit and deletion capabilities to be required and credited separate from management capability
  * Feature : WooCommerce - Ensure orders can be edited or added based on edit_shop_orders / create_shop_orders capability
  * Change : Lockout safeguard (preventing read capability removal) is bypassed if role has no WP admin / edit capabilities, or if it has "dashboard_lockout_ok" capability
  * Compat : Press Permit: new plugin page slugs in Press Permit Core 2.7

= 1.6.1 =
  * Feature : Prevent read capability from being removed from a standard role
  * Feature : If read capability is missing from a standard role, display warning and instant fix link
  * Feature : Additional save button at top of Roles and Capabilities screen!
  * Change : Reinstate Press Permit description link
  * Change : Thickbox popups for related plugins

= 1.6 =
  * Feature : WooCommerce - If current user has duplicate_products capability, make Woo honor it
  * Feature : Link to Backup Tool from sidebar of Roles and Capabilities screen
  * Feature : Link to Roles and Capabilities screen from Backup Tool
  * Change : Minor code cleanup and refactor
  * Change : Copyrights, onscreen link for PublishPress ownership
  * Change : Links to Related Permissions Plugins in sidebar on Roles and Capabilities screen

= 1.5.11 =
  * Feature : Automatically save backup of WP roles on plugin activation or update
  * Feature : When roles are manually backed up, also retain initial role backup
  * Feature : Backup Tool can also display contents of role backups

= 1.5.10 =
  * Fixed : Back button caused mismatching role dropdown selection
  * Compat : PHP 7.2 - warning for deprecated function if a second copy of CME is activated

= 1.5.9 =
  * Fixed : Potential vulnerability in wp-admin (but exposure was only to users with role editing capability)

= 1.5.8 =
  * Fixed : PHP warning for deprecated function WP_Roles::reinit
  * Change : Don't allow non-Administrator to edit Administrators, even if Administrator role level is set to 0

= 1.5.7 =
  * Change : Revert menu captions to previous behavior ("Permissions > Role Capabilities" if Press Permit Core is active, otherwise "Users > Capabilities")

= 1.5.6 =
  * Fixed : Correct some irregularities in CME admin menu item display

= 1.5.5 =
  * Fixed : User editing was improperly blocked in some cases

= 1.5.4 =
  * Fixed : Non-administrators' user editing capabilities were blocked if Press Permit Core was also active
  * Fixed : Non-administrators could not edit other users with their role (define constant CME_LEGACY_USER_EDIT_FILTER to retain previous behavior)
  * Fixed : Non-administrators could not assign their role to other users (define constant CME_LEGACY_USER_EDIT_FILTER to retain previous behavior)
  * Lang : Changed text domain for language pack conformance

= 1.5.3 =
  * Fixed : On single-site installations, non-Administrators with delete_users capability could give new users an Administrator role (since 1.5.2)
  * Fixed : Deletion of a third party plugin role could cause users to be demoted to Subscriber inappropriately
  * Compat : Press Permit Core - Permission Group refresh was not triggered if Press Permit Core is inactive when CME deletes a role definition
  * Compat : Support third party display of available capabilities via capsman_get_capabilities or members_get_capabilities filter
  * Change : If user_level of Administrator role was cleared, non-Administrators with user editing capabilities could create/edit/delete Administrators.  Administrator role is now implicitly treated as level 10.
  * Fixed : CSS caused formatting issues around wp-admin Update button on some installations
  * Perf : Don't output wp-admin CSS on non-CME screens
  * Lang : Fixed erroneous text_domain argument for numerous strings
  * Lang : Updated .pot and .po files

= 1.5.2 =
  * Fixed : Network Super Administrators without an Administrator role on a particular site could not assign an Administrator role to other users of that site

= 1.5.1 =
  * Fixed : Non-administrators with user editing capabilities could give new users a role with a higher level than their own (including Administrator)

= 1.5 =
  * Feature : Support negative capabilities (storage to wp_roles array with false value)
  * Feature : Multisite - Copy a role definition to all current sites on a network
  * Feature : Multisite - Copy a role definition to new (future) sites on a network
  * Feature : Backup / Restore tool requires "restore_roles" capability or super admin status
  * Fixed : Role reset to WP defaults did not work, caused a PHP error / white screen
  * Change : Clarified English captions on Backup Tool screen
  * Fixed : Term deletion capability was not included in taxonomies grid even if defined
  * Fixed : jQuery notices for deprecated methods on Edit Role screen
  * Compat : Press Permit - if a role is marked as hidden, also default it for use by PP Pro as a Pattern Role (when PP Collaborative Editing is activated and Advanced Settings enabled)
  * Change : Press Permit promotional message includes link to display further info

= 1.4.10 =
  * Perf :  Eliminated unused framework code (reduced typical wp-admin memory usage by 0.6 MB)
  * Fixed : Failure to save capability changes, on some versions of PHP
  * Compat : Press Permit - PHP Warning on role save
  * Compat : Press Permit - PHP Warning on "Force Type-Specific Capabilities" settings update
  * Compat : Press Permit - "supplemental only" option stored redundant entries
  * Compat : Press Permit - green background around capabilities which
  * Compat : Press Permit - PHP Warning on "Force Type-Specific Capabilities" settings update
  * Maint  : Stop using $GLOBALS superglobal
  * Change : Reduced download size by moving screenshots to assets folder of project folder

= 1.4.9 =
  * Fixed : Role capabilities were not updated / refreshed properly on multisite installations
  * Feature : If create_posts capabilities are defined, organize checkboxes into a column alongside edit_posts
  * Feature : "Use create_posts capability" checkbox in sidebar auto-defines create_posts capabilities (requires Press Permit)
  * Compat : bbPress + Press Permit - Modified bbPress role capabilities were not redisplayed following save, required reload
  * Compat : bbPress + Press Permit - Adding a capability via the "Add Cap" textbox caused the checkbox to be available but not selected
  * Compat : Press Permit - "supplemental only" option was always enabled for newly created and copied roles, regardless of checkbox setting near Create/Copy button

= 1.4.8 =
  * Compat : bbPress + Press Permit - "Add Capability" form failed when used on a bbPress role, caused creation of an invalid role

= 1.4.7 =
  * Compat : Press Permit - flagging of roles as "supplemental assignment only" was not saved

= 1.4.6 =
  * Compat : bbPress 2.2 (supports customization of dynamic forum role capabilities)
  * Compat : Press Permit + bbPress - customized role capabilities were not properly maintained on bbPress activation / deactivation, in some scenarios
  * Fixed : Role update and copy failed if currently stored capability array is corrupted

= 1.4.5 =
  * Fixed : Capabilities were needlessly re-saved on role load
  * Fixed : Capability labels in "Other WordPress" section did not toggle checkbox selection
  * Press Permit integration: If capability is granted by the role's Permit Group, highlight it as green with a descriptive caption title, but leave checkbox enabled for display/editing of role defintion setting (previous behavior caused capability to be stripped out of WP role definition under some PP configurations)

= 1.4.4 =
  * Fixed : On translated sites, roles could not be edited
  * Fixed : Menu item change to "Role Capabilities" broke existing translations

= 1.4.3 =
  * Fixed : Separate checkbox was displayed for cap->edit_published_posts even if it was defined to the be same as cap->edit_posts
  * Press Permit integration: automatically store a backup copy of each role's last saved capability set so they can be reinstated if necessary (currently for bbPress)

= 1.4.2 =
  * Language: updated .pot file
  * Press Permit integration: roles can be marked for supplemental assignment only (and suppressed from WP role assignment dropdown, requires PP 1.0-beta1.4)

= 1.4.1 =
  * https compatibility: use content_url(), plugins_url()
  * Press Permit integration: if role definitions are reset to WP defaults, also repopulate PP capabilities (pp_manage_settings, etc.)

= 1.4 =
  * Organized capabilities UI by post type and operation
  * Editing UI separates WP core capabilities and 3rd party capabilities
  * Clarified sidebar captions
  * Don't allow a non-Administrator to add or remove a capability they don't have
  * Fixed : PHP Warnings for unchecked capabilities
  * Press Permit integration: externally (dis)enable Post Types, Taxonomies for PP filtering (which forces type-specific capability definitions)
  * Show capabilities which Press Permit adds to the role by supplemental type-specific role assignment
  * Reduce memory usage by loading framework and plugin code only when needed

= 1.3.2 =
  * Added Swedish translation.

= 1.3.1 =
  * Fixed a bug where administrators could not create or manage other administrators.

= 1.3 =
  * Cannot edit users with more capabilities than current user.
  * Cannot assign to users a role with more capabilities than current user.
  * Solved an incompatibility with Chameleon theme.
  * Migrated to the new Alkivia Framework.
  * Changed license to GPL version 2.

= 1.2.5 =
  * Tested up to WP 2.9.1.

= 1.2.4 =
  * Added Italian translation.

= 1.2.3 =
  * Added German and Belorussian translations.

= 1.2.2 =
  * Added Russian translation.

= 1.2.1 =
  * Coding Standards.
  * Corrected internal links.
  * Updated Framework.

= 1.2 =
  * Added backup/restore tool.

= 1.1 =
  * Role deletion added.

= 1.0.1 =
  * Some code improvements.
  * Updated Alkivia Framework.

= 1.0 =
  * First public version.
