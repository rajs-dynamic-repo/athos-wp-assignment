# Excel Import Guide for Translation System

## Overview

The translation system now supports importing and exporting translations from Excel files (.xlsx, .xls) and CSV files alongside the existing JSON import functionality. This provides a more user-friendly way to manage translations using familiar spreadsheet applications.

## Features

### ✅ Available Now

- **Excel Import**: Upload .xlsx and .xls files with translation data
- **Excel Export**: Export current translations to native Excel format (.xlsx)
- **CSV Import**: Upload CSV files with translation data
- **CSV Export**: Export current translations to CSV format
- **Template Download**: Download a pre-filled CSV template
- **JSON Import**: Original JSON import functionality (unchanged)
- **Same Output**: REST API still returns the same JSON format

### 🎨 Excel Features

- **Styled Headers**: Blue background with white text
- **Auto-sized Columns**: Automatic column width adjustment
- **Multiple Locales**: Support for unlimited locale columns
- **Error Handling**: Clear error messages for invalid files

## How to Use

### 1. Import from Excel/CSV

#### Step 1: Prepare Your File

Create an Excel file (.xlsx, .xls) or CSV file with the following structure:

**Excel/CSV Format:**

```
| Key        | en_us      | es_es      | fr_ca      |
|------------|------------|------------|------------|
| dashboard  | Dashboard  | Panel      | Tableau    |
| settings   | Settings   | Configuración | Paramètres |
| profile    | Profile    | Perfil     | Profil     |
```

**Requirements:**

- First row must contain headers: `Key`, `en_us`, `es_es`, `fr_ca`
- First column contains translation keys
- Subsequent columns contain translations for each locale
- Empty cells are allowed (will be skipped)

#### Step 2: Upload and Import

1. Go to **Console Translations > Import Translations**
2. In the "Import from Excel" section, click "Choose File"
3. Select your Excel (.xlsx, .xls) or CSV file
4. Click "Import Excel"
5. Success message will confirm import

### 2. Download Template

#### Get Started Quickly

1. Go to **Console Translations > Import Translations**
2. Click "Download Excel Template"
3. Open the downloaded `translation_template.csv` file
4. Edit the translations in your preferred spreadsheet application
5. Save as Excel (.xlsx) or CSV and import back

### 3. Export Current Translations

#### Export for Editing

1. Go to **Console Translations > Import Translations**
2. In the "Export Translations" section:
   - Click "Export to CSV" for CSV format
   - Click "Export to Excel (.xlsx)" for native Excel format
3. Download the file with current translations
4. Edit in Excel/Google Sheets/other spreadsheet apps
5. Import the updated file

## File Format Examples

### Excel Template Structure

```
| Key        | en_us      | es_es      | fr_ca      |
|------------|------------|------------|------------|
| dashboard  | Dashboard  | Panel      | Tableau    |
| settings   | Settings   | Configuración | Paramètres |
| profile    | Profile    | Perfil     | Profil     |
| logout     | Logout     | Cerrar sesión | Déconnexion |
| save       | Save       | Guardar    | Enregistrer |
```

### CSV Template Structure

```csv
Key,en_us,es_es,fr_ca
dashboard,Dashboard,Panel,Tableau
settings,Settings,Configuración,Paramètres
profile,Profile,Perfil,Profil
logout,Logout,Cerrar sesión,Déconnexion
save,Save,Guardar,Enregistrer
```

### Supported Locales

The system supports any locale codes. Common ones include:

- `en_us` - English (US)
- `es_es` - Spanish (Spain)
- `fr_ca` - French (Canada)
- `de_de` - German (Germany)
- `it_it` - Italian (Italy)

## Workflow Examples

### Scenario 1: New Translation Project

1. Download the template: `translation_template.csv`
2. Add your translation keys and values
3. Import the Excel/CSV file
4. Translations are now available via REST API

### Scenario 2: Update Existing Translations

1. Export current translations to Excel (.xlsx) or CSV
2. Edit translations in Excel/Google Sheets
3. Import the updated file
4. Changes are immediately available

### Scenario 3: Add New Locale

1. Export current translations
2. Add a new column for the new locale (e.g., `de_de`)
3. Fill in the German translations
4. Import the updated file

## Technical Details

### Import Process

1. **File Validation**: Checks file format and readability
2. **Header Parsing**: Reads first row as column headers
3. **Data Processing**: Converts Excel/CSV rows to internal format
4. **Database Update**: Saves translations to WordPress posts
5. **Master JSON Update**: Updates the master translation structure

### Export Process

1. **Data Retrieval**: Gets all translation keys and locales
2. **Excel Generation**: Creates properly formatted Excel file with styling
3. **File Download**: Serves Excel file with current date in filename

### Error Handling

- **Invalid File Format**: Clear error messages for unsupported files
- **Missing Headers**: Validates required column structure
- **Empty Data**: Checks for valid translation content
- **File Permissions**: Handles file access issues
- **PhpSpreadsheet Errors**: Detailed error messages for Excel processing

## API Output (Unchanged)

The REST API continues to return the same JSON format:

```json
{
  "keys": [
    {
      "key": "dashboard",
      "translations": {
        "en_us": "Dashboard",
        "es_es": "Panel",
        "fr_ca": "Tableau"
      }
    }
  ]
}
```

## Troubleshooting

### Common Issues

**"File not found or not readable"**

- Check file permissions
- Ensure file is not corrupted
- Try uploading a smaller file

**"No valid translation data found"**

- Verify Excel/CSV has proper headers
- Check that data rows contain translation values
- Ensure no empty rows at the beginning

**"PhpSpreadsheet library is not available"**

- Ensure PhpSpreadsheet is installed via Composer
- Check that the library is properly loaded
- Verify PHP version compatibility

**"First column must be 'Key'"**

- Ensure the first column header is exactly "Key"
- Check for extra spaces or special characters
- Verify the file format is correct

### Best Practices

1. **Backup First**: Export current translations before importing
2. **Test Small**: Start with a few translations to test the process
3. **Use Templates**: Download and use the provided template
4. **Validate Data**: Check Excel/CSV format before importing
5. **Keep Copies**: Save working files for future reference
6. **Use Excel Format**: .xlsx files provide better formatting and features

## System Requirements

### PhpSpreadsheet Library

The Excel functionality requires the PhpSpreadsheet library:

```bash
composer require phpoffice/phpspreadsheet
```

### Supported File Formats

- **Excel 2007+**: .xlsx files (recommended)
- **Excel 97-2003**: .xls files
- **CSV**: .csv files (compatible with all spreadsheet apps)

### PHP Requirements

- PHP 7.4 or higher
- Extensions: zip, xml, gd (for Excel processing)

## Future Enhancements

### Planned Features

- **Multiple Worksheets**: Support for multiple sheets in Excel files
- **Advanced Styling**: More formatting options for exported files
- **Bulk Operations**: Import multiple files at once
- **Validation Rules**: Custom validation for translation content
- **Version Control**: Track changes and rollback capabilities
- **Translation Memory**: Suggest translations based on existing data
- **Conditional Formatting**: Highlight missing or incomplete translations

## Support

For issues or questions:

1. Check the troubleshooting section above
2. Verify your Excel/CSV format matches the examples
3. Test with the provided template file
4. Ensure PhpSpreadsheet library is properly installed
5. Contact development team for technical support
