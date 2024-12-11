<?php
/*
Plugin Name: WP POPUP MAGIC
Plugin URI: https://github.com/zonay/wpopup-magic
Description: A lightweight, customizable WordPress popup plugin built with modern technologies. Create engaging popups with Tailwind CSS and jQuery, perfect for announcements, newsletters, and promotional content.
Version: 1.0
Author: Zafer Onay
*/

// Add admin menu
function wpopup_admin_menu() {
    add_menu_page(
        'WP Popup Settings', // Page title
        'WP Popup', // Menu title
        'manage_options', // Capability
        'wpopup-settings', // Menu slug
        'wpopup_settings_page', // Function to display the page
        'dashicons-welcome-view-site', // Icon (you can change this to any dashicon)
        30 // Position
    );
    add_action('admin_init', 'wpopup_register_settings');
}
add_action('admin_menu', 'wpopup_admin_menu');

function wpopup_register_settings() {
    register_setting('wpopup_settings', 'wpopup_dev_mode');
    register_setting('wpopup_settings', 'wpopup_title');
    register_setting('wpopup_settings', 'wpopup_text');
    register_setting('wpopup_settings', 'wpopup_banner');
    register_setting('wpopup_settings', 'wpopup_banner_url'); // Add this line
    register_setting('wpopup_settings', 'wpopup_button_text');
    register_setting('wpopup_settings', 'wpopup_button_url');
    register_setting('wpopup_settings', 'wpopup_cookie_days');
    register_setting('wpopup_settings', 'wpopup_trigger_type');
    register_setting('wpopup_settings', 'wpopup_demo_mode'); // Add this line
}

function wpopup_settings_page() {
    ?>
    <div class="wrap">
        <h2>WP Popup Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('wpopup_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Development Mode</th>
                    <td>
                        <input type="checkbox" name="wpopup_dev_mode" value="1" <?php checked(1, get_option('wpopup_dev_mode'), true); ?> />
                        <span class="description">Enable to use non-minified CSS and live reload</span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Popup Title</th>
                    <td>
                        <input type="text" name="wpopup_title" value="<?php echo esc_attr(get_option('wpopup_title')); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Popup Text</th>
                    <td>
                        <textarea name="wpopup_text" class="large-text" rows="5"><?php echo esc_textarea(get_option('wpopup_text')); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Banner Image URL</th>
                    <td>
                        <input type="url" name="wpopup_banner" value="<?php echo esc_url(get_option('wpopup_banner')); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Banner Link URL (Optional)</th>
                    <td>
                        <input type="url" name="wpopup_banner_url" value="<?php echo esc_url(get_option('wpopup_banner_url')); ?>" class="regular-text" />
                        <p class="description">If set, the banner image will be clickable</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Button Text</th>
                    <td>
                        <input type="text" name="wpopup_button_text" value="<?php echo esc_attr(get_option('wpopup_button_text')); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Button URL</th>
                    <td>
                        <input type="url" name="wpopup_button_url" value="<?php echo esc_url(get_option('wpopup_button_url')); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Cookie Duration (days)</th>
                    <td>
                        <input type="number" name="wpopup_cookie_days" value="<?php echo esc_attr(get_option('wpopup_cookie_days', '7')); ?>" min="1" />
                        <p class="description">Number of days before showing the popup again to the same visitor</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Display Trigger</th>
                    <td>
                        <select name="wpopup_trigger_type">
                            <option value="init" <?php selected('init', get_option('wpopup_trigger_type', 'init')); ?>>Immediate</option>
                            <option value="scroll" <?php selected('scroll', get_option('wpopup_trigger_type')); ?>>On Scroll</option>
                        </select>
                        <p class="description">Choose when to display the popup</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Demo Mode</th>
                    <td>
                        <input type="checkbox" name="wpopup_demo_mode" value="1" <?php checked(1, get_option('wpopup_demo_mode'), true); ?> />
                        <span class="description">Enable Demo Mode to show popup based on URL parameter ?wpopup-demo=true</span>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Enqueue custom-built Tailwind CSS and jQuery
function wpopup_enqueue_scripts() {
    $dev_mode = get_option('wpopup_dev_mode');
    $css_file = $dev_mode ? 'css/tailwind.css' : 'css/tailwind.min.css';
    wp_enqueue_style('tailwind-css', plugin_dir_url(__FILE__) . $css_file);
    wp_enqueue_script('jquery');
}
add_action('wp_enqueue_scripts', 'wpopup_enqueue_scripts');

// Add popup HTML to footer
function wpopup_add_popup() {
    $dev_mode = get_option('wpopup_dev_mode');
    $demo_mode = get_option('wpopup_demo_mode'); // Add this line

    // Add demo mode URL check
    if ($demo_mode) {
        $is_demo = isset($_GET['wpopup-demo']) && $_GET['wpopup-demo'] === 'true';
        if (!$is_demo) {
            return;
        }
    }

    // Skip cookie check if dev mode is active
    if (!$dev_mode && isset($_COOKIE['wpopup_shown'])) {
        return;
    }

    $title = get_option('wpopup_title');
    $text = get_option('wpopup_text');
    $banner = get_option('wpopup_banner');
    $banner_url = get_option('wpopup_banner_url');
    $button_text = get_option('wpopup_button_text');
    $button_url = get_option('wpopup_button_url');
    ?>
    <div class="popup__overlay fixed z-10 inset-0 overflow-y-auto" id="wpoopupModal" style="display: none;">
        <div class="popup__container flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="popup__background fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="popup__background-inner absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="popup__content inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full relative">
                <button type="button" class="popup__close-button absolute top-3 right-3 text-gray-400 hover:text-gray-500" data-dismiss="modal">
                    <span class="text-2xl">&times;</span>
                </button>
                <?php if ($banner): ?>
                    <div class="popup__banner w-full">
                        <?php if ($banner_url): ?>
                            <a href="<?php echo esc_url($banner_url); ?>" class="popup__banner-link">
                        <?php endif; ?>
                        <img src="<?php echo esc_url($banner); ?>" alt="" class="popup__banner-image w-full h-48 md:h-64 object-cover">
                        <?php if ($banner_url): ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="popup__body bg-white px-[1.25rem] pt-[1.25rem] pb-4 sm:p-[1.25rem] sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="popup__text-container mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <?php if ($title): ?>
                                <h3 class="popup__title text-lg leading-6 font-medium text-gray-900"><?php echo esc_html($title); ?></h3>
                            <?php endif; ?>
                            <?php if ($text): ?>
                                <div class="popup__text mt-2">
                                    <p class="text-sm text-gray-500"><?php echo wp_kses_post($text); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if ($button_text && $button_url): ?>
                                <div class="popup__actions mt-4">
                                    <a href="<?php echo esc_url($button_url); ?>" class="popup__button inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:text-sm">
                                        <?php echo esc_html($button_text); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        function wpSetCookie(name, value, days) {
            var expires = "";
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toUTCString();
            }
            document.cookie = name + "=" + (value || "") + expires + "; path=/";
        }

        function wpGetCookie(name) {
            var nameEQ = name + "=";
            var ca = document.cookie.split(';');
            for(var i=0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0)==' ') c = c.substring(1,c.length);
                if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
            }
            return null;
        }

        jQuery(document).ready(function($) {
            var devMode = <?php echo $dev_mode ? 'true' : 'false'; ?>;
            var demoMode = <?php echo $demo_mode ? 'true' : 'false'; ?>; // Add this line
            var popupShown = false;
            var triggerType = '<?php echo esc_js(get_option('wpopup_trigger_type', 'init')); ?>';
            var isDemo = new URLSearchParams(window.location.search).get('wpopup-demo') === 'true';

            function showPopup() {
                if (!popupShown && (devMode || (demoMode && isDemo) || !wpGetCookie('wpopup_shown'))) {
                    $('#wpoopupModal').fadeIn();
                    popupShown = true;
                }
            }

            // Handle different trigger types
            if (triggerType === 'scroll') {
                $(window).scroll(function() {
                    if ($(this).scrollTop() > 300) {
                        showPopup();
                    }
                });
            } else {
                showPopup();
            }
            
            // Close on outside click
            $(document).mouseup(function(e) {
                var popup = $('.popup__content');
                if (!popup.is(e.target) && popup.has(e.target).length === 0) {
                    $('#wpoopupModal').fadeOut();
                    if (!devMode && !(demoMode && isDemo)) {
                        wpSetCookie('wpopup_shown', '1', <?php echo intval(get_option('wpopup_cookie_days', '7')); ?>);
                    }
                }
            });

            // Close on X button click
            $('[data-dismiss="modal"]').on('click', function() {
                $('#wpoopupModal').fadeOut();
                if (!devMode && !(demoMode && isDemo)) {
                    wpSetCookie('wpopup_shown', '1', <?php echo intval(get_option('wpopup_cookie_days', '7')); ?>);
                }
            });
        });
    </script>
    <?php
}
add_action('wp_footer', 'wpopup_add_popup');
?>
