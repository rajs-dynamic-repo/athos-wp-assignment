<?php
// Include PhpSpreadsheet autoloader if available
if (file_exists(ABSPATH . 'vendor/autoload.php')) {
  require_once ABSPATH . 'vendor/autoload.php';
}

/**
 *
 * Theme Functions
 * Sets up theme defaults and registers support for various WordPress features.
 * 
 * @package WordPress
 * @subpackage Athos-Custom-Theme
 * 
 */


// Remove WordPress version number from the header to improve security  
remove_action('wp_head', 'wp_generator');

// Following function sets the Content Security Policy (CSP) headers for enhanced security. *
function custom_csp_headers()
{
  header("Content-Security-Policy: script-src 'self' 'strict-dynamic' 'unsafe-inline' https:; script-src-elem 'self' 'unsafe-inline' https:; object-src 'none'; base-uri 'none';");
}
add_action('send_headers', 'custom_csp_headers');


//Following function register template selection dropwdown to wp-dashboard
function register_custom_templates($post_templates, $wp_theme, $post)
{
  $post_templates['template-homepage.php'] = __('Homepage Custom Template', 'your-textdomain');
  return $post_templates;
}
add_filter('theme_page_templates', 'register_custom_templates', 10, 3);


// Enqueue header and footer styles from the CSS folder
function enqueue_custom_styles()
{
  wp_enqueue_style('global-style', get_stylesheet_directory_uri() . '/assets/css/global.css');
  wp_enqueue_style('header-style', get_stylesheet_directory_uri() . '/assets/css/header.css');
  wp_enqueue_style('footer-style', get_stylesheet_directory_uri() . '/assets/css/footer.css');
  wp_enqueue_style('homepage-style', get_stylesheet_directory_uri() . '/assets/css/homepage.css');
  wp_enqueue_style('contactUs-style', get_stylesheet_directory_uri() . '/assets/css/contact-us.css');
}
add_action('wp_enqueue_scripts', 'enqueue_custom_styles');

function enqueue_gsap_scripts()
{
  // Core GSAP library
  wp_enqueue_script('gsap-core', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js', array(), '3.12.2', true);
}
add_action('wp_enqueue_scripts', 'enqueue_gsap_scripts');


// This Function is to Process the form submissions
function process_custom_form()
{
  if (isset($_POST['custom_form_submit'])) {
    $to = 'rajdelegend@gmail.com';
    $subject = 'New Audit Request from Website';

    $body = "First Name: " . sanitize_text_field($_POST['firstName']) . "\n";
    $body .= "Last Name: " . sanitize_text_field($_POST['lastName']) . "\n";
    $body .= "Email: " . sanitize_email($_POST['email']) . "\n";
    $body .= "Phone: " . sanitize_text_field($_POST['phone']) . "\n";
    $body .= "Company: " . sanitize_text_field($_POST['company']) . "\n";
    $body .= "Website: " . esc_url_raw($_POST['website']) . "\n";

    $headers = array('Content-Type: text/html; charset=UTF-8');

    wp_mail($to, $subject, $body, $headers);

    // Redirect to same page with success parameter
    wp_redirect(add_query_arg('form_success', 'true', wp_get_referer()));
    exit;
  }
}
add_action('init', 'process_custom_form');


// Function for Sitemap
function add_sitemap_rewrite_rule()
{
  add_rewrite_rule('sitemap\.xml$', 'wp-content/themes/Athos-Custom-Theme/sitemap.php', 'top');
}
add_action('init', 'add_sitemap_rewrite_rule');


// Function for Robots.txt 
function custom_robots_txt($output, $public)
{
  $output = "User-agent: *\n";
  $output .= "Disallow: /wp-admin/\n";
  $output .= "Disallow: /wp-includes/\n";
  $output .= "Allow: /wp-content/uploads/\n";
  $output .= "Sitemap: " . home_url('/sitemap.xml') . "\n";
  return $output;
}
add_filter('robots_txt', 'custom_robots_txt', 10, 2);


// Remove a single top-level locale wrapper from data
function unwrap_locale_wrapper($data)
{
  if (is_array($data) && count($data) === 1) {
    $first_key = array_keys($data)[0];
    if (preg_match('/^[a-z]{2}[-_][a-z]{2}$/i', $first_key)) {
      return $data[$first_key];
    }
  }
  return $data;
}

add_action('rest_api_init', function () {
  register_rest_route('athos-console-translations/v1', '/locale/(?P<slug>[a-zA-Z0-9_\-]+)', array(
    'methods' => 'GET',
    'callback' => function ($data) {
      $locale = strtolower($data['slug']);
      $master = get_option('translation_master_json', []);
      if (empty($master)) {
        return new WP_Error('no_master', 'Master JSON (en_US) not found', array('status' => 500));
      }
      if ($locale === 'en_us') {
        return unwrap_locale_wrapper($master);
      }
      $locale_data = get_option('translation_' . $locale . '_data', []);
      $merged = merge_locale_into_master($master, $locale_data);
      return unwrap_locale_wrapper($merged);
    },
    'permission_callback' => '__return_true'
  ));
});

// merge locale values into master structure
function merge_locale_into_master($master, $locale_data)
{
  $result = [];
  foreach ($master as $key => $master_value) {
    if (is_array($master_value)) {
      $result[$key] = isset($locale_data[$key]) && is_array($locale_data[$key])
        ? merge_locale_into_master($master_value, $locale_data[$key])
        : merge_locale_into_master($master_value, []);
    } else {
      $result[$key] = isset($locale_data[$key]) ? $locale_data[$key] : '';
    }
  }
  return $result;
}

// Extract locale from filename (e.g., en_US.json -> en_US)
function extract_locale_from_filename($filename)
{
  $basename = basename($filename);
  if (preg_match('/^([a-z]{2}_[A-Z]{2})\.json$/', $basename, $matches)) {
    return $matches[1];
  }
  return false;
}

// Refactored import function
function import_translations_auto($json_data, $locale)
{
  $data = json_decode($json_data, true);
  if (json_last_error() !== JSON_ERROR_NONE) {
    error_log('IMPORT ERROR: Invalid JSON for ' . $locale . ': ' . json_last_error_msg());
    return new WP_Error('invalid_json', 'Invalid JSON format: ' . json_last_error_msg());
  }
  if (!is_array($data)) {
    error_log('IMPORT ERROR: Not an array for ' . $locale);
    return new WP_Error('invalid_structure', 'JSON must be an object/array');
  }

  // Auto-detect and remove a single top-level locale wrapper
  if (count($data) === 1) {
    $first_key = array_keys($data)[0];
    // If the first key looks like a locale (en-us, en_us, es-es, es_es, etc), unwrap it
    if (preg_match('/^[a-z]{2}[-_][a-z]{2}$/i', $first_key)) {
      $data = $data[$first_key];
      error_log('IMPORT: Removed top-level locale wrapper "' . $first_key . '" for ' . $locale);
    }
  }

  if (strtolower($locale) === 'en_us') {
    update_option('translation_master_json', $data);
    update_option('translation_en_us_data', $data);
    error_log('IMPORT: Saved en_us as master and en_us data: ' . print_r($data, true));
  } else {
    $master = get_option('translation_master_json', []);
    if (empty($master)) {
      error_log('IMPORT ERROR: No master JSON found when importing ' . $locale);
      return new WP_Error('no_master', 'Master JSON (en_US) must be imported first.');
    }
    error_log('IMPORT: Filtering locale ' . $locale . ' with master: ' . print_r($master, true) . ' and locale data: ' . print_r($data, true));
    $filtered = filter_locale_data_by_master($data, $master, $locale);
    error_log('IMPORT: Filtered data for ' . $locale . ': ' . print_r($filtered, true));
    update_option('translation_' . strtolower($locale) . '_data', $filtered);
  }

  $post_title = strtoupper($locale) . ' Translations';
  $post_name = strtolower($locale);
  $existing_post = get_page_by_path($post_name, OBJECT, 'console-translations');
  if ($existing_post) {
    $post_id = $existing_post->ID;
    wp_update_post([
      'ID' => $post_id,
      'post_title' => $post_title,
      'post_status' => 'publish'
    ]);
  } else {
    $post_id = wp_insert_post([
      'post_title' => $post_title,
      'post_name' => $post_name,
      'post_type' => 'console-translations',
      'post_status' => 'publish'
    ]);
  }
  return true;
}

// filter locale data to match master structure
function filter_locale_data_by_master($locale_data, $master_data, $locale = '')
{
  $filtered = [];
  foreach ($master_data as $key => $master_value) {
    if (is_array($master_value)) {
      $filtered[$key] = isset($locale_data[$key]) && is_array($locale_data[$key])
        ? filter_locale_data_by_master($locale_data[$key], $master_value, $locale)
        : filter_locale_data_by_master([], $master_value, $locale);
    } else {
      $filtered[$key] = isset($locale_data[$key]) ? $locale_data[$key] : '';
      if (!isset($locale_data[$key])) {
        error_log('FILTER: Missing key "' . $key . '" for locale ' . $locale . ', setting empty string.');
      } else {
        error_log('FILTER: Set key "' . $key . '" for locale ' . $locale . ' to value: ' . print_r($locale_data[$key], true));
      }
    }
  }
  return $filtered;
}

// Refactored import page
function render_import_translations_page_auto()
{
  $message = '';
  if (isset($_FILES['locale_json_file']) && $_FILES['locale_json_file']['error'] === UPLOAD_ERR_OK && check_admin_referer('import_translations_action', 'import_translations_nonce')) {
    $json_data = file_get_contents($_FILES['locale_json_file']['tmp_name']);
    $filename = $_FILES['locale_json_file']['name'];
    $locale = extract_locale_from_filename($filename);
    if (!$locale) {
      $message = '<div class="error"><p>Could not detect locale from filename. Use format en_US.json, es_ES.json, etc.</p></div>';
    } else {
      $result = import_translations_auto($json_data, $locale);
      if (is_wp_error($result)) {
        $message = '<div class="error"><p>Import failed: ' . esc_html($result->get_error_message()) . '</p></div>';
      } else {
        $message = '<div class="updated"><p>' . strtoupper($locale) . ' translations imported successfully!</p></div>';
      }
    }
  }
  // Export logic
  global $wpdb;
  $posts = get_posts([
    'post_type' => 'console-translations',
    'post_status' => 'publish',
    'numberposts' => -1
  ]);
  $available_locales = [];
  foreach ($posts as $post) {
    $available_locales[] = $post->post_name;
  }
?>
  <div class="wrap">
    <h1>Translation Import/Export</h1>
    <?php echo $message; ?>
    <div class="notice notice-info">
      <p><strong>Upload a JSON file named like en_US.json, es_ES.json, etc. The system will auto-detect the locale and create/update the template.</strong></p>
    </div>
    <div class="import-methods">
      <div class="import-section">
        <h2>Import Locale JSON</h2>
        <form method="post" enctype="multipart/form-data">
          <?php wp_nonce_field('import_translations_action', 'import_translations_nonce'); ?>
          <p>Upload a JSON file for a specific locale (filename must be en_US.json, es_ES.json, etc.):</p>
          <p>
            <input type="file" name="locale_json_file" accept=".json" required>
          </p>
          <p>
            <input type="submit" value="Import JSON" class="button button-primary">
          </p>
        </form>
      </div>
      <div class="import-section">
        <h2>Export Locale JSON</h2>
        <p>Export existing translations to JSON format:</p>
        <form method="post">
          <?php wp_nonce_field('import_translations_action', 'import_translations_nonce'); ?>
          <p>
            <label>Select Locale:</label><br>
            <select name="export_locale" required>
              <option value="">Choose locale...</option>
              <?php foreach ($available_locales as $locale): ?>
                <option value="<?php echo esc_attr($locale); ?>"><?php echo esc_html(strtoupper($locale)); ?></option>
              <?php endforeach; ?>
            </select>
          </p>
          <p>
            <input type="submit" value="Export JSON" class="button button-primary">
          </p>
        </form>
      </div>
    </div>
    <style>
      .import-methods {
        display: flex;
        gap: 20px;
        margin-top: 20px;
        flex-wrap: wrap;
      }

      .import-section {
        flex: 1;
        min-width: 300px;
        background: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #ddd;
      }

      .import-section h2 {
        margin-top: 0;
        color: #23282d;
        font-size: 18px;
      }

      .import-section input[type="file"],
      .import-section select {
        margin: 10px 0;
        display: block;
      }

      .import-section input[type="submit"] {
        margin-right: 10px;
        margin-bottom: 5px;
      }
    </style>
  </div>
<?php
}

// Update the menu to use the import page
add_action('admin_menu', function () {
  add_submenu_page(
    'edit.php?post_type=console-translations',
    'Import Translations',
    'Import Translations',
    'manage_options',
    'import-translations',
    'render_import_translations_page_auto'
  );
});

// Translation Editor Admin UI
add_action('admin_menu', function () {
  add_submenu_page(
    'edit.php?post_type=console-translations',
    'Translation Editor',
    'Translation Editor',
    'manage_options',
    'translation-editor',
    'render_translation_editor_page'
  );
});

function render_translation_editor_page()
{
  // Get all available locales
  $posts = get_posts([
    'post_type' => 'console-translations',
    'post_status' => 'publish',
    'numberposts' => -1
  ]);
  $available_locales = [];
  foreach ($posts as $post) {
    $available_locales[] = $post->post_name;
  }
  $selected_locale = isset($_GET['locale']) ? sanitize_text_field($_GET['locale']) : (count($available_locales) ? $available_locales[0] : '');
  $is_master = strtolower($selected_locale) === 'en_us';

  // save updates
  $message = '';
  if (isset($_POST['save_translations']) && check_admin_referer('translation_editor_action', 'translation_editor_nonce')) {
    $new_data = json_decode(stripslashes($_POST['hierarchical_json']), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
      $message = '<div class="error"><p>Invalid JSON: ' . json_last_error_msg() . '</p></div>';
    } else {
      if ($is_master) {
        update_option('translation_master_json', $new_data);
        update_option('translation_en_us_data', $new_data);
        // Sync structure for all other locales
        foreach ($available_locales as $loc) {
          if (strtolower($loc) !== 'en_us') {
            $locale_data = get_option('translation_' . strtolower($loc) . '_data', []);
            $filtered = filter_locale_data_by_master($locale_data, $new_data);
            update_option('translation_' . strtolower($loc) . '_data', $filtered);
          }
        }
        $message = '<div class="updated"><p>Master JSON and all locales updated!</p></div>';
      } else {
        // Only update values for existing keys
        $master = get_option('translation_master_json', []);
        $filtered = filter_locale_data_by_master($new_data, $master, $selected_locale);
        update_option('translation_' . strtolower($selected_locale) . '_data', $filtered);
        $message = '<div class="updated"><p>Locale updated!</p></div>';
      }
    }
    error_log('TRANSLATION EDITOR SAVE: locale=' . $selected_locale . ' data=' . print_r($_POST['hierarchical_json'], true));
  }

  // Get data for display
  $master = get_option('translation_master_json', []);
  $locale_data = $is_master
    ? $master
    : get_option('translation_' . strtolower($selected_locale) . '_data', []);


?>
  <div class="wrap">
    <h1>Translation Editor</h1>
    <?php echo $message; ?>
    <form method="get" action="<?php echo admin_url('admin.php'); ?>" style="margin-bottom:20px;">
      <input type="hidden" name="page" value="translation-editor" />
      <label><strong>Select Locale:</strong></label>
      <select name="locale" onchange="this.form.submit()">
        <?php foreach ($available_locales as $loc): ?>
          <option value="<?php echo esc_attr($loc); ?>" <?php selected($selected_locale, $loc); ?>><?php echo esc_html(strtoupper($loc)); ?></option>
        <?php endforeach; ?>
      </select>
    </form>
    <form method="post" id="translation-editor-form">
      <?php wp_nonce_field('translation_editor_action', 'translation_editor_nonce'); ?>
      <input type="hidden" name="locale" value="<?php echo esc_attr($selected_locale); ?>" />
      <h2><?php echo esc_html(strtoupper($selected_locale)); ?> Translations</h2>
      <p>Edit the translations below. <?php if ($is_master): ?><strong>You can add/remove keys and change structure for en_US (master).</strong><?php else: ?><strong>You can only edit values for this locale.</strong><?php endif; ?></p>
      <div style="margin-bottom:10px;">
        <button type="button" class="button" onclick="expandAllRows()">Expand All</button>
        <button type="button" class="button" onclick="collapseAllRows()">Collapse All</button>
      </div>
      <div id="translation-tree"></div>
      <textarea name="hierarchical_json" id="hierarchical_json" style="display:none;"></textarea>
      <p><input type="submit" name="save_translations" value="Save Changes" class="button button-primary" /></p>
    </form>
    <script>
      // Hierarchical Tree Editor
      const isMaster = <?php echo $is_master ? 'true' : 'false'; ?>;
      let data = <?php echo json_encode($locale_data, JSON_PRETTY_PRINT); ?>;
      let master = <?php echo json_encode($master, JSON_PRETTY_PRINT); ?>;
      const container = document.getElementById('translation-tree');

      function renderTree(obj, parent, path = []) {
        for (const key in obj) {
          const value = obj[key];
          const row = document.createElement('div');
          row.style.marginLeft = (path.length * 20) + 'px';
          row.className = 'tree-row';
          // Key name 
          let keyInput;
          if (isMaster) {
            keyInput = document.createElement('input');
            keyInput.type = 'text';
            keyInput.value = key;
            keyInput.className = 'tree-key';
            keyInput.style.width = '180px';
            keyInput.onchange = function() {
              const oldKey = key;
              const newKey = keyInput.value;
              if (!newKey) return;
              // Rename key in data
              let ref = data;
              for (let i = 0; i < path.length; i++) ref = ref[path[i]];
              ref[newKey] = ref[oldKey];
              delete ref[oldKey];
              render();
            };
          } else {
            keyInput = document.createElement('span');
            keyInput.textContent = key;
            keyInput.className = 'tree-key';
            keyInput.style.width = '180px';
          }
          row.appendChild(keyInput);
          if (typeof value === 'object' && value !== null) {
            // Expand/collapse button
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = '[+]';
            btn.className = 'tree-toggle';
            btn.onclick = function() {
              const next = row.nextSibling;
              let show = true;
              let sibling = next;
              while (sibling && sibling.className === 'tree-row') {
                if (sibling.style.display === 'none') show = false;
                sibling.style.display = sibling.style.display === 'none' ? '' : 'none';
                sibling = sibling.nextSibling;
              }
              btn.textContent = btn.textContent === '[+]' ? '[-]' : '[+]';
            };
            row.insertBefore(btn, keyInput);
            parent.appendChild(row);
            renderTree(value, parent, path.concat([key]));
          } else {
            const valInput = document.createElement('input');
            valInput.type = 'text';
            valInput.value = value;
            valInput.className = 'tree-value';
            valInput.style.width = '300px';
            valInput.onchange = function() {
              let ref = data;
              for (let i = 0; i < path.length; i++) ref = ref[path[i]];
              ref[key] = valInput.value;
              document.getElementById('hierarchical_json').value = JSON.stringify(data);
            };
            row.appendChild(valInput);
            if (isMaster) {
              const delBtn = document.createElement('button');
              delBtn.type = 'button';
              delBtn.textContent = 'Delete';
              delBtn.className = 'tree-delete';
              delBtn.onclick = function() {
                let ref = data;
                for (let i = 0; i < path.length; i++) ref = ref[path[i]];
                delete ref[key];
                render();
              };
              row.appendChild(delBtn);
            }
            parent.appendChild(row);
          }
        }
        // Add new key (master only, at this level)
        if (isMaster) {
          const addRow = document.createElement('div');
          addRow.style.marginLeft = (path.length * 20) + 'px';
          const addBtn = document.createElement('button');
          addBtn.type = 'button';
          addBtn.textContent = '+ Add Key';
          addBtn.onclick = function() {
            let ref = data;
            for (let i = 0; i < path.length; i++) ref = ref[path[i]];
            let newKey = prompt('Enter new key name:');
            if (!newKey) return;
            if (ref[newKey]) {
              alert('Key already exists!');
              return;
            }
            ref[newKey] = '';
            render();
          };
          addRow.appendChild(addBtn);
          parent.appendChild(addRow);
        }
      }

      function render() {
        container.innerHTML = '';
        renderTree(data, container, []);
        document.getElementById('hierarchical_json').value = JSON.stringify(data);
      }

      function expandAllRows() {
        document.querySelectorAll('.tree-row').forEach(row => row.style.display = '');
        document.querySelectorAll('.tree-toggle').forEach(btn => btn.textContent = '[-]');
      }

      function collapseAllRows() {
        document.querySelectorAll('.tree-row').forEach(row => row.style.display = '');
        document.querySelectorAll('.tree-toggle').forEach(btn => btn.textContent = '[+]');
      }
      // Ensure latest data is saved before submit
      document.getElementById('translation-editor-form').addEventListener('submit', function(e) {
        document.getElementById('hierarchical_json').value = JSON.stringify(data);
      });
      render();
    </script>
    <style>
      .tree-row {
        margin-bottom: 4px;
      }

      .tree-key {
        display: inline-block;
        font-weight: bold;
        margin-right: 10px;
      }

      .tree-value {
        display: inline-block;
        margin-right: 10px;
      }

      .tree-delete {
        margin-left: 10px;
        color: #a00;
      }

      .tree-toggle {
        margin-right: 8px;
      }
    </style>
  </div>
<?php
}
