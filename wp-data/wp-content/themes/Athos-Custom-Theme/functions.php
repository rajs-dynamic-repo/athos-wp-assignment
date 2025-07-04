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


// Function for refined Console Translations output in JSON (Post Meta)
add_action('rest_api_init', function () {
  register_rest_route('athos-console-translations/v1', '/locale/(?P<slug>[a-zA-Z0-9_-]+)', array(
    'methods' => 'GET',
    'callback' => function ($data) {
      $args = array(
        'name'        => $data['slug'],
        'post_type'   => 'console-translations',
        'post_status' => 'publish',
        'numberposts' => 1
      );
      $posts = get_posts($args);
      if (empty($posts)) {
        return new WP_Error('not_found', 'Translation not found', array('status' => 404));
      }

      $post_id = $posts[0]->ID;

      // Get the master JSON to know which fields should exist
      $master_json = get_option('translation_master_json', ['keys' => []]);
      $expected_keys = array_column($master_json['keys'], 'key');

      // Get translations directly from post meta
      $clean_translations = array();

      foreach ($expected_keys as $key) {
        $value = get_post_meta($post_id, $key, true);
        if (!empty($value)) {
          $clean_translations[$key] = trim($value);
        }
      }

      // Sort keys alphabetically for consistent output
      ksort($clean_translations);

      return $clean_translations;
    },
    'permission_callback' => '__return_true'
  ));
});

// Step 2: Manage Master JSON
add_action('admin_menu', function () {
  add_submenu_page(
    'edit.php?post_type=console-translations',
    'Manage Translation Keys',
    'Manage Keys',
    'manage_options',
    'manage-keys',
    'render_manage_keys_page'
  );
});

function render_manage_keys_page()
{
  $master_json = get_option('translation_master_json', ['keys' => []]);
  $message = '';

  if ($_POST['master_json'] && check_admin_referer('manage_keys_action', 'manage_keys_nonce')) {
    $result = process_translation_json(wp_unslash($_POST['master_json']));
    if (is_wp_error($result)) {
      $message = '<div class="error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
    } else {
      update_option('translation_master_json', $result);
      import_translations($result);
      $message = '<div class="updated"><p>Keys and translations updated successfully!</p></div>';
    }
  }

  if ($_POST['export_json'] && check_admin_referer('manage_keys_action', 'manage_keys_nonce')) {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="translation_master.json"');
    echo json_encode($master_json, JSON_PRETTY_PRINT);
    exit;
  }
?>
  <div class="wrap">
    <h1>Manage Translation Keys</h1>
    <?php echo $message; ?>
    <form method="post">
      <?php wp_nonce_field('manage_keys_action', 'manage_keys_nonce'); ?>
      <p>Paste or edit the master JSON below. Format: {"keys": [{"key": "key_name", "translations": {"locale_slug": "value"}}]}</p>
      <textarea name="master_json" rows="15" style="width:100%"><?php echo esc_textarea(json_encode($master_json, JSON_PRETTY_PRINT)); ?></textarea>
      <input type="submit" value="Save and Sync" class="button button-primary">
      <input type="submit" name="export_json" value="Export JSON" class="button">
    </form>
  </div>
<?php
}

// Step 3: Bulk Import Translations
add_action('admin_menu', function () {
  add_submenu_page(
    'edit.php?post_type=console-translations',
    'Import Translations',
    'Import Translations',
    'manage_options',
    'import-translations',
    'render_import_translations_page'
  );
});

// Handle download actions before page rendering
add_action('admin_init', function () {
  if (isset($_POST['download_excel_template']) && check_admin_referer('import_translations_action', 'import_translations_nonce')) {
    download_excel_template();
  }

  if (isset($_POST['export_to_excel']) && check_admin_referer('import_translations_action', 'import_translations_nonce')) {
    export_translations_to_excel();
  }

  if (isset($_POST['export_to_excel_native']) && check_admin_referer('import_translations_action', 'import_translations_nonce')) {
    export_translations_to_excel_native();
  }
});

// Add AJAX actions for downloads
add_action('wp_ajax_download_excel_template', 'download_excel_template');
add_action('wp_ajax_export_translations_csv', 'export_translations_to_excel');
add_action('wp_ajax_export_translations_excel', 'export_translations_to_excel_native');

function render_import_translations_page()
{
  $message = '';

  // Handle JSON import (existing functionality)
  if (isset($_FILES['translation_file']) && $_FILES['translation_file']['error'] === UPLOAD_ERR_OK && check_admin_referer('import_translations_action', 'import_translations_nonce')) {
    $json_data = file_get_contents($_FILES['translation_file']['tmp_name']);
    $result = process_translation_json($json_data);
    if (is_wp_error($result)) {
      $message = '<div class="error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
    } else {
      update_option('translation_master_json', $result);
      import_translations($result);
      $message = '<div class="updated"><p>JSON translations imported successfully!</p></div>';
    }
  }

  // Handle Excel import (new functionality)
  if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK && check_admin_referer('import_translations_action', 'import_translations_nonce')) {
    $result = process_translation_excel($_FILES['excel_file']['tmp_name'], $_FILES['excel_file']['name']);
    if (is_wp_error($result)) {
      $message = '<div class="error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
    } else {
      update_option('translation_master_json', $result);
      import_translations($result);
      $message = '<div class="updated"><p>Excel translations imported successfully!</p></div>';
    }
  }


?>
  <div class="wrap">
    <h1>Import Translations</h1>
    <?php echo $message; ?>

    <div class="import-methods">
      <!-- JSON Import Section (Existing) -->
      <div class="import-section">
        <h2>Import from JSON</h2>
        <form method="post" enctype="multipart/form-data">
          <?php wp_nonce_field('import_translations_action', 'import_translations_nonce'); ?>
          <p>Upload a JSON file with translations. Format: {"keys": [{"key": "key_name", "translations": {"locale_slug": "value"}}]}</p>
          <input type="file" name="translation_file" accept=".json">
          <input type="submit" value="Import JSON" class="button button-primary">
        </form>
      </div>

      <!-- Excel Import Section (New) -->
      <div class="import-section">
        <h2>Import from Excel</h2>
        <form method="post" enctype="multipart/form-data">
          <?php wp_nonce_field('import_translations_action', 'import_translations_nonce'); ?>
          <p>Upload an Excel file (.xlsx, .xls) or CSV file with translations. First row should contain headers: Key, en_us, es_es, fr_ca</p>
          <input type="file" name="excel_file" accept=".xlsx,.xls,.csv">
          <input type="submit" value="Import Excel" class="button button-primary">
        </form>
        <p><a href="<?php echo admin_url('admin-ajax.php?action=download_excel_template&_wpnonce=' . wp_create_nonce('import_translations_action')); ?>" class="button">Download Excel Template</a></p>
      </div>

      <!-- Export Section -->
      <div class="import-section">
        <h2>Export Translations</h2>
        <p>Export current translations to Excel/CSV format for editing.</p>
        <p><a href="<?php echo admin_url('admin-ajax.php?action=export_translations_csv&_wpnonce=' . wp_create_nonce('import_translations_action')); ?>" class="button button-primary">Export to CSV</a></p>
        <p><a href="<?php echo admin_url('admin-ajax.php?action=export_translations_excel&_wpnonce=' . wp_create_nonce('import_translations_action')); ?>" class="button">Export to Excel (.xlsx)</a></p>
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

      .import-section input[type="file"] {
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

// Step 4: Replace main content area with Translation Editor
add_action('add_meta_boxes', function () {
  // Remove the default editor
  remove_post_type_support('console-translations', 'editor');

  // Add our custom translation editor as the main content
  add_meta_box(
    'translation_editor_main',
    'Translation Editor',
    'render_translation_editor_main',
    'console-translations',
    'normal',
    'high'
  );
});

function render_translation_editor_main($post)
{
  // Get the master JSON to know which fields should exist
  $master_json = get_option('translation_master_json', ['keys' => []]);
  $expected_keys = array_column($master_json['keys'], 'key');

  // Get current values
  $current_values = array();
  foreach ($expected_keys as $key) {
    $current_values[$key] = get_post_meta($post->ID, $key, true);
  }

  wp_nonce_field('save_translations', 'translation_nonce');
?>
  <div class="translation-editor-main">
    <div class="notice notice-info">
      <p><strong>Edit translations for: <?php echo esc_html($post->post_title); ?></strong></p>
    </div>

    <table class="translation-fields-table">
      <tbody>
        <?php foreach ($expected_keys as $key): ?>
          <tr>
            <th class="translation-key-label">
              <?php echo esc_html($key); ?>
            </th>
            <td>
              <input
                type="text"
                id="<?php echo esc_attr($key); ?>"
                name="translation_<?php echo esc_attr($key); ?>"
                value="<?php echo esc_attr($current_values[$key]); ?>"
                class="large-text"
                placeholder="Enter translation for <?php echo esc_attr($key); ?>" />
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <style>
      .translation-editor-main {
        padding: 20px 0;
        background: #fff9db;
        border-radius: 8px;
        margin-top: 10px;
      }

      .translation-fields-table {
        width: 90%;
        max-width: 800px;
        border-collapse: separate;
        border-spacing: 0 10px;
        margin-left: 0;
      }

      .translation-fields-table th {
        text-align: left;
        vertical-align: middle;
        padding: 8px 20px 8px 10px;
        font-weight: 600;
        color: #23282d;
        width: 200px;
        white-space: nowrap;
      }

      .translation-fields-table input[type="text"] {
        padding: 4px 5px;
        border: 1px solid #ddd;
        border-radius: 3px;
        font-size: 13px;
      }

      .translation-fields-table input[type="text"]:focus {
        border-color: #0073aa;
        box-shadow: 0 0 0 1px #0073aa;
        outline: none;
      }
    </style>
  </div>
<?php
}

// Save translations when post is saved
add_action('save_post', function ($post_id) {
  // Check if this is our post type
  if (get_post_type($post_id) !== 'console-translations') {
    return;
  }

  // Verify nonce
  if (!isset($_POST['translation_nonce']) || !wp_verify_nonce($_POST['translation_nonce'], 'save_translations')) {
    return;
  }

  // Get the master JSON to know which fields should exist
  $master_json = get_option('translation_master_json', ['keys' => []]);
  $expected_keys = array_column($master_json['keys'], 'key');

  // Save each translation
  foreach ($expected_keys as $key) {
    $field_name = 'translation_' . $key;
    if (isset($_POST[$field_name])) {
      $value = sanitize_text_field($_POST[$field_name]);
      update_post_meta($post_id, $key, $value);
    }
  }
});

// Functions to process the translation JSON
function process_translation_json($json_data)
{
  $data = json_decode($json_data, true);
  if (!$data || !isset($data['keys']) || !is_array($data['keys'])) {
    return new WP_Error('invalid_json', 'Invalid JSON format. Ensure it includes a "keys" array.');
  }

  $sanitized_data = ['keys' => []];
  foreach ($data['keys'] as $key_data) {
    if (!isset($key_data['key']) || !isset($key_data['translations']) || !is_array($key_data['translations'])) {
      continue;
    }
    $sanitized_key = sanitize_key($key_data['key']);
    $sanitized_translations = [];
    foreach ($key_data['translations'] as $locale => $translation) {
      $sanitized_translations[sanitize_text_field($locale)] = sanitize_text_field($translation);
    }
    $sanitized_data['keys'][] = [
      'key' => $sanitized_key,
      'translations' => $sanitized_translations
    ];
  }

  if (empty($sanitized_data['keys'])) {
    return new WP_Error('empty_data', 'No valid keys or translations found.');
  }

  return $sanitized_data;
}

function import_translations($data)
{
  foreach ($data['keys'] as $key_data) {
    $key = $key_data['key'];
    foreach ($key_data['translations'] as $locale => $translation) {
      if ($key === '' || $locale === '') {
        continue;
      }
      $posts = get_posts([
        'post_type' => 'console-translations',
        'name' => $locale,
        'post_status' => 'publish',
        'numberposts' => 1
      ]);
      if ($posts) {
        $post_id = $posts[0]->ID;
      } else {
        $post_id = wp_insert_post([
          'post_type' => 'console-translations',
          'post_status' => 'publish',
          'post_name' => $locale,
          'post_title' => $locale
        ]);
      }
      // Save directly to post meta (NO ACF)
      update_post_meta($post_id, $key, $translation);
    }
  }
}

// Remove support for title and editor for console-translations
add_action('init', function () {
  remove_post_type_support('console-translations', 'title');
  remove_post_type_support('console-translations', 'editor');
});

// Hide any remaining title/editor UI in the block editor for console-translations
add_action('admin_head', function () {
  $screen = get_current_screen();
  if ($screen && $screen->post_type === 'console-translations') {
    echo '<style>
            #titlediv, .edit-post-title-wrapper, #post-title-0, .block-editor-block-list__layout, .edit-post-visual-editor {
                display: none !important;
            }
        </style>';
  }
});

// Excel Import Functions
function process_translation_excel($file_path, $original_filename)
{
  // Check if file exists and is readable
  if (!file_exists($file_path) || !is_readable($file_path)) {
    return new WP_Error('file_error', 'File not found or not readable.');
  }

  $file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));

  // Handle CSV files
  if ($file_extension === 'csv') {
    return process_csv_file($file_path);
  }

  // Handle Excel files (.xlsx, .xls)
  if (in_array($file_extension, ['xlsx', 'xls'])) {
    return process_excel_file($file_path);
  }

  return new WP_Error('unsupported_format', 'Unsupported file format. Please use .xlsx, .xls, or .csv files.');
}

function process_csv_file($file_path)
{
  $handle = fopen($file_path, 'r');
  if (!$handle) {
    return new WP_Error('file_error', 'Could not open CSV file.');
  }

  $data = [];
  $row_number = 0;

  while (($row = fgetcsv($handle)) !== false) {
    $row_number++;

    // Skip empty rows
    if (empty(array_filter($row))) {
      continue;
    }

    // First row should be headers
    if ($row_number === 1) {
      $headers = array_map('trim', $row);
      continue;
    }

    // Process data rows
    if (isset($headers) && count($row) >= 2) {
      $key = trim($row[0]);
      if (empty($key)) {
        continue;
      }

      $translations = [];
      for ($i = 1; $i < count($headers); $i++) {
        $locale = trim($headers[$i]);
        $translation = isset($row[$i]) ? trim($row[$i]) : '';
        if (!empty($locale) && !empty($translation)) {
          $translations[$locale] = $translation;
        }
      }

      if (!empty($translations)) {
        $data[] = [
          'key' => $key,
          'translations' => $translations
        ];
      }
    }
  }

  fclose($handle);

  if (empty($data)) {
    return new WP_Error('empty_data', 'No valid translation data found in CSV file.');
  }

  return ['keys' => $data];
}

function process_excel_file($file_path)
{
  // Check if PhpSpreadsheet is available
  if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
    return new WP_Error('phpspreadsheet_not_available', 'PhpSpreadsheet library is not available. Please install it via Composer.');
  }

  try {
    // Load the Excel file
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);

    // Get the first worksheet
    $worksheet = $spreadsheet->getActiveSheet();

    // Get the highest row and column numbers
    $highestRow = $worksheet->getHighestRow();
    $highestColumn = $worksheet->getHighestColumn();
    $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

    // Get headers from first row
    $headers = [];
    for ($col = 1; $col <= $highestColumnIndex; $col++) {
      $cellValue = $worksheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '1')->getValue();
      $headers[$col] = trim($cellValue);
    }

    // Validate headers
    if (empty($headers[1]) || strtolower($headers[1]) !== 'key') {
      return new WP_Error('invalid_headers', 'First column must be "Key". Found: ' . $headers[1]);
    }

    $data = [];

    // Process data rows (starting from row 2)
    for ($row = 2; $row <= $highestRow; $row++) {
      $key = trim($worksheet->getCell('A' . $row)->getValue());

      // Skip empty rows
      if (empty($key)) {
        continue;
      }

      $translations = [];

      // Process each column (starting from column 2)
      for ($col = 2; $col <= $highestColumnIndex; $col++) {
        $locale = trim($headers[$col]);
        $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
        $translation = trim($worksheet->getCell($columnLetter . $row)->getValue());

        if (!empty($locale) && !empty($translation)) {
          $translations[$locale] = $translation;
        }
      }

      if (!empty($translations)) {
        $data[] = [
          'key' => $key,
          'translations' => $translations
        ];
      }
    }

    if (empty($data)) {
      return new WP_Error('empty_data', 'No valid translation data found in Excel file.');
    }

    return ['keys' => $data];
  } catch (Exception $e) {
    return new WP_Error('excel_processing_error', 'Error processing Excel file: ' . $e->getMessage());
  }
}

function download_excel_template()
{
  // Verify nonce for AJAX requests
  if (isset($_GET['_wpnonce'])) {
    if (!wp_verify_nonce($_GET['_wpnonce'], 'import_translations_action')) {
      wp_die('Security check failed');
    }
  } else {
    // For POST requests, use the old nonce check
    if (!check_admin_referer('import_translations_action', 'import_translations_nonce')) {
      wp_die('Security check failed');
    }
  }

  // Prevent any output before headers
  if (ob_get_level()) {
    ob_end_clean();
  }

  // Create CSV template content
  $template_content = "Key,en_us,es_es,fr_ca\n";
  $template_content .= "dashboard,Dashboard,Panel,Tableau\n";
  $template_content .= "settings,Settings,Configuración,Paramètres\n";
  $template_content .= "profile,Profile,Perfil,Profil\n";
  $template_content .= "logout,Logout,Cerrar sesión,Déconnexion\n";
  $template_content .= "save,Save,Guardar,Enregistrer\n";
  $template_content .= "cancel,Cancel,Cancelar,Annuler\n";
  $template_content .= "delete,Delete,Eliminar,Supprimer\n";
  $template_content .= "edit,Edit,Editar,Modifier\n";
  $template_content .= "add,Add,Agregar,Ajouter\n";
  $template_content .= "search,Search,Buscar,Rechercher\n";

  // Set headers for download
  nocache_headers();
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="translation_template.csv"');
  header('Content-Length: ' . strlen($template_content));

  // Output the CSV content
  echo $template_content;
  exit;
}

function export_translations_to_excel()
{
  // Verify nonce for AJAX requests
  if (isset($_GET['_wpnonce'])) {
    if (!wp_verify_nonce($_GET['_wpnonce'], 'import_translations_action')) {
      wp_die('Security check failed');
    }
  } else {
    // For POST requests, use the old nonce check
    if (!check_admin_referer('import_translations_action', 'import_translations_nonce')) {
      wp_die('Security check failed');
    }
  }

  // Get the master JSON to know which keys exist
  $master_json = get_option('translation_master_json', ['keys' => []]);

  if (empty($master_json['keys'])) {
    wp_die('No translation keys found. Please import some translations first.');
  }

  // Get all locale posts
  $locale_posts = get_posts([
    'post_type' => 'console-translations',
    'post_status' => 'publish',
    'numberposts' => -1
  ]);

  // Create a map of locale slugs
  $locales = [];
  foreach ($locale_posts as $post) {
    $locales[] = $post->post_name;
  }

  if (empty($locales)) {
    wp_die('No translation locales found. Please create some translation posts first.');
  }

  // Create CSV content
  $csv_content = "Key," . implode(',', $locales) . "\n";

  foreach ($master_json['keys'] as $key_data) {
    $key = $key_data['key'];
    $row = [$key];

    foreach ($locales as $locale) {
      $posts = get_posts([
        'post_type' => 'console-translations',
        'name' => $locale,
        'post_status' => 'publish',
        'numberposts' => 1
      ]);

      if ($posts) {
        $translation = get_post_meta($posts[0]->ID, $key, true);
        $row[] = $translation ?: '';
      } else {
        $row[] = '';
      }
    }

    $csv_content .= implode(',', array_map('csv_escape', $row)) . "\n";
  }

  // Prevent any output before headers
  if (ob_get_level()) {
    ob_end_clean();
  }

  // Set headers for download
  nocache_headers();
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="translations_export_' . date('Y-m-d') . '.csv"');
  header('Content-Length: ' . strlen($csv_content));

  echo $csv_content;
  exit;
}

function csv_escape($value)
{
  // Escape CSV values properly
  if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
    return '"' . str_replace('"', '""', $value) . '"';
  }
  return $value;
}

function export_translations_to_excel_native()
{
  // Verify nonce for AJAX requests
  if (isset($_GET['_wpnonce'])) {
    if (!wp_verify_nonce($_GET['_wpnonce'], 'import_translations_action')) {
      wp_die('Security check failed');
    }
  } else {
    // For POST requests, use the old nonce check
    if (!check_admin_referer('import_translations_action', 'import_translations_nonce')) {
      wp_die('Security check failed');
    }
  }

  // Check if PhpSpreadsheet is available
  if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
    wp_die('PhpSpreadsheet library is not available. Please install it via Composer.');
  }

  // Get the master JSON to know which keys exist
  $master_json = get_option('translation_master_json', ['keys' => []]);

  if (empty($master_json['keys'])) {
    wp_die('No translation keys found. Please import some translations first.');
  }

  // Get all locale posts
  $locale_posts = get_posts([
    'post_type' => 'console-translations',
    'post_status' => 'publish',
    'numberposts' => -1
  ]);

  // Create a map of locale slugs
  $locales = [];
  foreach ($locale_posts as $post) {
    $locales[] = $post->post_name;
  }

  if (empty($locales)) {
    wp_die('No translation locales found. Please create some translation posts first.');
  }

  try {
    // Create new Spreadsheet object
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set headers
    $sheet->setCellValue('A1', 'Key');
    $col = 2;
    foreach ($locales as $locale) {
      $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '1', $locale);
      $col++;
    }

    // Style headers
    $headerStyle = [
      'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
      ],
      'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4472C4'],
      ],
    ];
    $sheet->getStyle('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col - 1) . '1')->applyFromArray($headerStyle);

    // Add data
    $row = 2;
    foreach ($master_json['keys'] as $key_data) {
      $key = $key_data['key'];
      $sheet->setCellValue('A' . $row, $key);

      $col = 2;
      foreach ($locales as $locale) {
        $posts = get_posts([
          'post_type' => 'console-translations',
          'name' => $locale,
          'post_status' => 'publish',
          'numberposts' => 1
        ]);

        if ($posts) {
          $translation = get_post_meta($posts[0]->ID, $key, true);
          $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row, $translation ?: '');
        } else {
          $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row, '');
        }
        $col++;
      }
      $row++;
    }

    // Auto-size columns
    foreach (range('A', \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col - 1)) as $column) {
      $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    // Create Excel file
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

    // Prevent any output before headers
    if (ob_get_level()) {
      ob_end_clean();
    }

    // Set headers for download
    nocache_headers();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="translations_export_' . date('Y-m-d') . '.xlsx"');

    // Output file
    $writer->save('php://output');
    exit;
  } catch (Exception $e) {
    wp_die('Error creating Excel file: ' . $e->getMessage());
  }
}
