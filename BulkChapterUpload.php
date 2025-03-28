<?php
/*
Plugin Name: Bulk Chapter Upload
Description: Bulk upload chapters from a zip file with password protection and expiration.
Version: 1.0
Author: sharnabeel
authorlink: https://github.com/Nabeelshar
*/
if (!defined('ABSPATH')) exit;

function bulk_chapter_log_error($message) {
    if (WP_DEBUG) {
        error_log('[Bulk Chapter Upload] ' . $message);
    }
}

add_action('admin_menu', 'bulk_chapter_upload_menu');
function bulk_chapter_upload_menu() {
    add_menu_page(
        'Bulk Chapter Upload',
        'Bulk Chapter Upload',
        'manage_options',
        'bulk-chapter-upload',
        'bulk_chapter_upload_page'
    );
}

function bulk_chapter_upload_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    if (isset($_POST['submit']) && !empty($_FILES['zip_file']['name'])) {
        bulk_chapter_upload_handle_upload();
    }
    
    $stories = get_posts(array(
        'post_type' => 'fcn_story',
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ));
    
    ?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <?php settings_errors('bulk_chapter_upload'); ?>
    <?php if (empty($stories)): ?>
    <div class="notice notice-warning">
        <p><?php _e('No stories found. Please create a story first.', 'fictioneer'); ?></p>
    </div>
    <?php else: ?>
    <form method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('bulk_chapter_upload', 'bulk_chapter_nonce'); ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="story_id"><?php _e('Select Story:', 'fictioneer'); ?></label>
                </th>
                <td>
                    <select name="story_id" id="story_id" required>
                        <option value=""><?php _e('-- Select Story --', 'fictioneer'); ?></option>
                        <?php foreach ($stories as $story): ?>
                        <option value="<?php echo esc_attr($story->ID); ?>">
                            <?php echo esc_html($story->post_title); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="post_status"><?php _e('Chapter Status:', 'fictioneer'); ?></label>
                </th>
                <td>
                    <select name="post_status" id="post_status" required>
                        <option value="draft"><?php _e('Draft', 'fictioneer'); ?></option>
                        <option value="publish"><?php _e('Published', 'fictioneer'); ?></option>
                    </select>
                    <p class="description">
                        <?php _e('Select whether to create chapters as drafts or publish them immediately.', 'fictioneer'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="zip_file"><?php _e('Upload Zip File:', 'fictioneer'); ?></label>
                </th>
                <td>
                    <input type="file" name="zip_file" id="zip_file" accept=".zip" required>
                    <p class="description">
                        <?php _e('Upload a ZIP file containing text files (.txt). Each text file will be imported as a chapter.', 'fictioneer'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="schedule_type"><?php _e('Schedule Type:', 'fictioneer'); ?></label>
                </th>
                <td>
                    <select name="schedule_type" id="schedule_type">
                        <option value="none"><?php _e('No Scheduling', 'fictioneer'); ?></option>
                        <option value="single"><?php _e('Single Date (All Chapters)', 'fictioneer'); ?></option>
                        <option value="increment"><?php _e('Daily Increment', 'fictioneer'); ?></option>
                        <option value="weekly"><?php _e('Weekly Schedule', 'fictioneer'); ?></option>
                    </select>
                </td>
            </tr>
            <!-- Single Date Scheduling Options -->
            <tr class="schedule-options schedule-single" style="display: none;">
                <th scope="row">
                    <label for="schedule_date"><?php _e('Schedule Date:', 'fictioneer'); ?></label>
                </th>
                <td>
                    <input type="datetime-local" name="schedule_date" id="schedule_date">
                </td>
            </tr>
            <!-- Input field for delay minutes -->
            <tr class="schedule-options schedule-single" style="display: none;">
                <th scope="row">
                    <label for="delay_minutes"><?php _e('Delay (Minutes):', 'fictioneer'); ?></label>
                </th>
                <td>
                    <input type="number" name="delay_minutes" id="delay_minutes" min="0" value="0">
                    <p class="description">
                        <?php _e('Number of minutes to delay each subsequent chapter.', 'fictioneer'); ?>
                    </p>
                </td>
            </tr>
            <!-- Input field for expiry start date -->
            <tr class="schedule-options schedule-single" style="display: none;">
                <th scope="row">
                    <label for="expiry_start_date"><?php _e('Expiry Start Date:', 'fictioneer'); ?></label>
                </th>
                <td>
                    <input type="datetime-local" name="expiry_start_date" id="expiry_start_date">
                    <p class="description">
                        <?php _e('Set the starting date for password expiration.', 'fictioneer'); ?>
                    </p>
                </td>
            </tr>
            <!-- Daily Increment Scheduling Options -->
            <tr class="schedule-options schedule-increment" style="display: none;">
                <th scope="row">
                    <label for="increment_start_date"><?php _e('Start Date:', 'fictioneer'); ?></label>
                </th>
                <td>
                    <input type="datetime-local" name="increment_start_date" id="increment_start_date">
                </td>
            </tr>
            <tr class="schedule-options schedule-increment" style="display: none;">
                <th scope="row">
                    <label for="increment_step"><?php _e('Step (Days):', 'fictioneer'); ?></label>
                </th>
                <td>
                    <input type="number" name="increment_step" id="increment_step" min="1" value="1">
                    <p class="description">
                        <?php _e('Number of days between each chapter publication.', 'fictioneer'); ?>
                    </p>
                </td>
            </tr>
            <!-- Weekly Scheduling Options -->
            <tr class="schedule-options schedule-weekly" style="display: none;">
                <th scope="row">
                    <label><?php _e('Publishing Days:', 'fictioneer'); ?></label>
                </th>
                <td>
                    <?php
                    $days = array(
                        'monday' => __('Monday', 'fictioneer'),
                        'tuesday' => __('Tuesday', 'fictioneer'),
                        'wednesday' => __('Wednesday', 'fictioneer'),
                        'thursday' => __('Thursday', 'fictioneer'),
                        'friday' => __('Friday', 'fictioneer'),
                        'saturday' => __('Saturday', 'fictioneer'),
                        'sunday' => __('Sunday', 'fictioneer')
                    );
                    foreach ($days as $value => $label): ?>
                    <label style="display: inline-block; margin-right: 15px;">
                        <input type="checkbox" name="schedule_days[]" value="<?php echo $value; ?>">
                        <?php echo $label; ?>
                    </label>
                    <?php endforeach; ?>
                    <p class="description">
                        <?php _e('Select days when chapters should be published.', 'fictioneer'); ?>
                    </p>
                </td>
            </tr>
            <tr class="schedule-options schedule-weekly" style="display: none;">
                <th scope="row">
                    <label for="weekly_time"><?php _e('Publishing Time:', 'fictioneer'); ?></label>
                </th>
                <td>
                    <input type="time" name="weekly_time" id="weekly_time">
                </td>
            </tr>
            <!-- Password Field -->
            <tr>
                <th scope="row">
                    <label for="chapter_password"><?php _e('Chapter Password:', 'fictioneer'); ?></label>
                </th>
                <td>
                    <input type="password" name="chapter_password" id="chapter_password" 
                           minlength="4" required autocomplete="new-password">
                    <p class="description">
                        <?php _e('Password to protect all chapters (4+ characters).', 'fictioneer'); ?>
                    </p>
                </td>
            </tr>
            <!-- Expire Count Field -->
            <tr>
                <th scope="row">
                    <label for="expire_count"><?php _e('Expire Count (Days):', 'fictioneer'); ?></label>
                </th>
                <td>
                    <input type="number" name="expire_count" id="expire_count" min="0" step="1" value="0" required>
                    <p class="description">
                        <?php _e('Number of days to add per chapter (multiplied by chapter number) for password expiration.', 'fictioneer'); ?>
                    </p>
                </td>
            </tr>
            <!-- Chapter Categories Selection (Dropdown with multiple selection) -->
            <tr>
                <th scope="row">
                    <label for="chapter_categories"><?php _e('Chapter Categories:', 'fictioneer'); ?></label>
                </th>
                <td>
                    <select name="chapter_categories[]" id="chapter_categories" multiple style="min-width:200px;">
                        <?php 
                        $categories = get_categories(array('hide_empty' => false));
                        foreach ($categories as $cat) : ?>
                            <option value="<?php echo esc_attr($cat->term_id); ?>">
                                <?php echo esc_html($cat->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Select one or more categories for the chapter.', 'fictioneer'); ?></p>
                </td>
            </tr>
        </table>
        <?php submit_button(__('Upload Chapters', 'fictioneer')); ?>
    </form>
    <?php endif; ?>
</div>
<script>
jQuery(document).ready(function($) {
    $('#schedule_type').change(function() {
        $('.schedule-options').hide();
        $('.schedule-' + $(this).val()).show();
    });
});
</script>
<?php
}

function bulk_chapter_upload_handle_upload() {
    if (!isset($_POST['bulk_chapter_nonce']) || !wp_verify_nonce($_POST['bulk_chapter_nonce'], 'bulk_chapter_upload')) {
        wp_die(__('Security check failed', 'fictioneer'));
    }
    
    bulk_chapter_log_error('Starting upload process...');
    
    $story_id = isset($_POST['story_id']) ? intval($_POST['story_id']) : 0;
    if (!$story_id || get_post_type($story_id) !== 'fcn_story') {
        bulk_chapter_log_error('Invalid story ID: ' . $story_id);
        add_settings_error(
            'bulk_chapter_upload',
            'invalid_story',
            __('Invalid story selected.', 'fictioneer'),
            'error'
        );
        return;
    }
    
    if (!isset($_FILES['zip_file']) || $_FILES['zip_file']['error'] !== UPLOAD_ERR_OK) {
        bulk_chapter_log_error('File upload error: ' . $_FILES['zip_file']['error']);
        add_settings_error(
            'bulk_chapter_upload',
            'upload_error',
            __('File upload failed. Error code: ', 'fictioneer') . $_FILES['zip_file']['error'],
            'error'
        );
        return;
    }
    
    $zip_file = $_FILES['zip_file'];
    if ($zip_file['type'] !== 'application/zip' && $zip_file['type'] !== 'application/x-zip-compressed') {
        bulk_chapter_log_error('Invalid file type: ' . $zip_file['type']);
        add_settings_error(
            'bulk_chapter_upload',
            'invalid_file',
            __('Please upload a valid ZIP file.', 'fictioneer'),
            'error'
        );
        return;
    }
    
    $upload_dir = wp_upload_dir();
    $temp_dir = $upload_dir['path'] . '/bulk-chapters-' . uniqid();
    $zip_path = $temp_dir . '/' . sanitize_file_name($zip_file['name']);
    
    if (!wp_mkdir_p($temp_dir)) {
        bulk_chapter_log_error('Failed to create temp directory: ' . $temp_dir);
        add_settings_error(
            'bulk_chapter_upload',
            'dir_failed',
            __('Failed to create temporary directory.', 'fictioneer'),
            'error'
        );
        return;
    }
    
    if (!move_uploaded_file($zip_file['tmp_name'], $zip_path)) {
        bulk_chapter_log_error('Failed to move uploaded file to: ' . $zip_path);
        add_settings_error(
            'bulk_chapter_upload',
            'upload_failed',
            __('Failed to upload ZIP file.', 'fictioneer'),
            'error'
        );
        return;
    }
    
    $zip = new ZipArchive;
    $zip_result = $zip->open($zip_path);
    if ($zip_result !== TRUE) {
        bulk_chapter_log_error('Failed to open ZIP file. Error code: ' . $zip_result);
        add_settings_error(
            'bulk_chapter_upload',
            'zip_failed',
            __('Failed to open ZIP file.', 'fictioneer'),
            'error'
        );
        @unlink($zip_path);
        @rmdir($temp_dir);
        return;
    }
    
    $zip->extractTo($temp_dir);
    $zip->close();
    
    $chapter_files = glob($temp_dir . '/*.txt');
    if (empty($chapter_files)) {
        bulk_chapter_log_error('No .txt files found in ZIP');
        add_settings_error(
            'bulk_chapter_upload',
            'no_files',
            __('No text files found in ZIP archive.', 'fictioneer'),
            'error'
        );
        cleanup_temp_files($temp_dir);
        return;
    }
    
    $chapters_created = 0;
    $errors = array();
    $post_status = isset($_POST['post_status']) && in_array($_POST['post_status'], ['draft', 'publish']) 
                  ? $_POST['post_status'] 
                  : 'draft';
    $password = isset($_POST['chapter_password']) ? sanitize_text_field($_POST['chapter_password']) : '';
    $expire_count = isset($_POST['expire_count']) ? intval($_POST['expire_count']) : 0;
    
    if (strlen($password) < 4) {
        $errors[] = __('Password must be at least 4 characters long.', 'fictioneer');
    }
    
    foreach ($chapter_files as $index => $file) {
        $content = file_get_contents($file);
        if ($content === false) {
            $errors[] = sprintf(__('Failed to read file: %s', 'fictioneer'), basename($file));
            continue;
        }
        
        $title = basename($file, '.txt');
        $scheduled_date = get_scheduled_date($index, $_POST['schedule_type']);
        
        $chapter_data = array(
            'post_title' => sanitize_text_field($title),
            'post_content' => wp_kses_post($content),
            'post_type' => 'fcn_chapter',
            'post_status' => $post_status,
            'post_password' => $password
        );
        
        if ($scheduled_date) {
            $chapter_data['post_status'] = 'future';
            $chapter_data['post_date'] = $scheduled_date->format('Y-m-d H:i:s');
            $chapter_data['post_date_gmt'] = get_gmt_from_date($chapter_data['post_date']);
        }
        
        $chapter_id = wp_insert_post($chapter_data, true);
        if (is_wp_error($chapter_id)) {
            bulk_chapter_log_error('Failed to create chapter: ' . $chapter_id->get_error_message());
            $errors[] = sprintf(__('Failed to create chapter %s: %s', 'fictioneer'), 
                              $title, 
                              $chapter_id->get_error_message());
            continue;
        }
        
        if ($chapter_id) {
            update_post_meta($chapter_id, 'fictioneer_chapter_story', $story_id);
            
            // Set categories if provided (from the dropdown)
            if ( !empty($_POST['chapter_categories']) && is_array($_POST['chapter_categories']) ) {
                $categories = array_map('intval', $_POST['chapter_categories']);
                wp_set_post_categories($chapter_id, $categories);
            }
            
            // Set expiration date
           // Inside the loop where chapters are processed
	if ($expire_count > 0) {
  	 	 $post = get_post($chapter_id);
   	 if ($post) {
      	  if (isset($_POST['schedule_type']) && $_POST['schedule_type'] === 'single' && !empty($_POST['expiry_start_date'])) {
            // Convert local time to GMT
            $expiry_start_date_local = str_replace('T', ' ', $_POST['expiry_start_date']);
            $expiry_start_date_gmt_str = get_gmt_from_date($expiry_start_date_local);
            $expiry_start_date = new DateTime($expiry_start_date_gmt_str, new DateTimeZone('UTC'));
            $multiplier = $index + 1;
            $days_to_add = $expire_count * $multiplier;
            $expiry_start_date->modify("+$days_to_add days");
            $expiration_date_gmt = $expiry_start_date;
        } else {
            $post_date_gmt = new DateTime($post->post_date_gmt);
            $expiration_date_gmt = clone $post_date_gmt;
            $expiration_date_gmt->modify("+$expire_count days");
        }
        update_post_meta(
            $chapter_id,
            'fictioneer_post_password_expiration_date',
            $expiration_date_gmt->format('Y-m-d H:i:s')
        );
    }
}            
            if ($post_status === 'publish') {
                handle_fictioneer_chapter_submission($chapter_id);
            } else {
                fictioneer_append_chapter_to_story($chapter_id, $story_id, false);
            }
            
            $chapters_created++;
        }
    }
    
    cleanup_temp_files($temp_dir);
    
    if ($chapters_created > 0) {
        add_settings_error(
            'bulk_chapter_upload',
            'upload_success',
            sprintf(
                __('Successfully created %d password-protected chapters.', 'fictioneer'),
                $chapters_created
            ),
            'success'
        );
    }
    
    if (!empty($errors)) {
        foreach ($errors as $error) {
            add_settings_error(
                'bulk_chapter_upload',
                'upload_partial_failure',
                $error,
                'error'
            );
        }
    }
}

function cleanup_temp_files($temp_dir) {
    array_map('unlink', glob("$temp_dir/*.*"));
    @rmdir($temp_dir);
}

add_action('admin_notices', function() {
    settings_errors('bulk_chapter_upload');
});

function handle_fictioneer_chapter_submission($post_id) {
    $uploads_dir = wp_upload_dir();
    $log_file = $uploads_dir['basedir'] . '/append.txt';
    $selected_value = get_post_meta($post_id, 'fictioneer_chapter_story', true);
    
    if (!empty($selected_value)) {
        $story_id = $selected_value;
        if ($story_id) {
            fictioneer_append_chapter_to_story($post_id, $story_id);
            file_put_contents($log_file, 'Chapter Appended successfully. ID ' . $story_id . PHP_EOL, FILE_APPEND);
        } else {
            file_put_contents($log_file, 'Invalid story ID: ' . $story_id . PHP_EOL, FILE_APPEND);
        }
        
        // Preserve password when updating
        $password = get_post_field('post_password', $post_id);
        remove_action('publish_fcn_chapter', 'handle_fictioneer_chapter_submission');
        wp_update_post(array(
            'ID' => $post_id,
            'post_password' => $password
        ));
        add_action('publish_fcn_chapter', 'handle_fictioneer_chapter_submission');
        
        update_post_meta($post_id, 'fictioneer_chapter_story', strval($story_id));
    } else {
        file_put_contents($log_file, 'Missing fictioneer_chapter_story in the form submission.' . PHP_EOL, FILE_APPEND);
    }
}

add_action('publish_fcn_chapter', 'handle_fictioneer_chapter_submission');

function get_scheduled_date($index, $schedule_type) {
    if (!isset($_POST['schedule_type'])) return null;
    switch ($_POST['schedule_type']) {
        case 'single':
            if (!empty($_POST['schedule_date'])) {
                $base_date = new DateTime($_POST['schedule_date']);
                // Read delay minutes input and apply delay per chapter based on index
                $delay_minutes = !empty($_POST['delay_minutes']) ? intval($_POST['delay_minutes']) : 0;
                if ($delay_minutes > 0 && $index > 0) {
                    $total_delay = $index * $delay_minutes;
                    $base_date->modify("+{$total_delay} minutes");
                }
                return $base_date;
            }
            break;
        case 'increment':
            if (!empty($_POST['increment_start_date'])) {
                $start_date = new DateTime($_POST['increment_start_date']);
                $step = isset($_POST['increment_step']) ? max(1, intval($_POST['increment_step'])) : 1;
                $days = $index * $step;
                return $start_date->modify("+$days days");
            }
            break;
        case 'weekly':
            if (!empty($_POST['schedule_days']) && !empty($_POST['weekly_time'])) {
                $selected_days = $_POST['schedule_days'];
                $time = $_POST['weekly_time'];
                $start_date = new DateTime();
                $day_count = 0;
                $current_day = clone $start_date;
                
                while ($day_count <= $index) {
                    $day_name = strtolower($current_day->format('l'));
                    if (in_array($day_name, $selected_days)) {
                        if ($day_count == $index) {
                            list($hours, $minutes) = explode(':', $time);
                            $current_day->setTime($hours, $minutes);
                            return $current_day;
                        }
                        $day_count++;
                    }
                    $current_day->modify('+1 day');
                }
            }
            break;
    }
    return null;
}
