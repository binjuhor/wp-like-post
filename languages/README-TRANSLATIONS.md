# Translation Files

This directory contains translation files for the Like System plugin.

## Files

- **wplp.pot**: Template file for creating new translations
- **wplp-ja.po**: Japanese translation source file
- **wplp-ja.mo**: Japanese compiled translation (binary file)

## How to Compile .mo Files

The `.mo` files are compiled binary versions of the `.po` files. WordPress reads `.mo` files for translations.

### Method 1: Using Poedit (Recommended for Beginners)

1. Download and install [Poedit](https://poedit.net/)
2. Open the `.po` file in Poedit
3. Click **File > Save** or press Ctrl/Cmd+S
4. Poedit will automatically generate the `.mo` file

### Method 2: Using WP-CLI

```bash
# Navigate to plugin directory
cd /path/to/wp-content/plugins/wp-like-post

# Compile all .po files to .mo files
wp i18n make-mo languages/

# Or compile a specific file
msgfmt languages/wplp-ja.po -o languages/wplp-ja.mo
```

### Method 3: Using Loco Translate (WordPress Plugin)

1. Install and activate [Loco Translate](https://wordpress.org/plugins/loco-translate/)
2. Go to **Loco Translate > Plugins** in WordPress admin
3. Select "Like System"
4. Edit or create translations
5. Click **Save** - the `.mo` file is generated automatically

### Method 4: Using msgfmt Command Line

```bash
# Install gettext (if not already installed)
# On macOS:
brew install gettext

# On Ubuntu/Debian:
sudo apt-get install gettext

# Compile the .po file
msgfmt -o languages/wplp-ja.mo languages/wplp-ja.po
```

## Creating New Translations

To create a translation for a new language:

1. **Copy the template:**
   ```bash
   cp wplp.pot wplp-{locale}.po
   ```
   Replace `{locale}` with your language code (e.g., `fr_FR`, `de_DE`, `es_ES`)

2. **Edit the header:**
   Update the `Language` and `Language-Team` fields in the `.po` file

3. **Translate strings:**
   - Use Poedit, Loco Translate, or any text editor
   - Translate the `msgstr` values (leave `msgid` unchanged)

4. **Compile:**
   Generate the `.mo` file using one of the methods above

5. **Test:**
   - Upload both `.po` and `.mo` files to the `languages/` directory
   - Change WordPress language in **Settings > General**
   - Check that translations appear correctly

## Language Codes

Common WordPress language codes:

- English (US): `en_US`
- Japanese: `ja`
- French: `fr_FR`
- German: `de_DE`
- Spanish: `es_ES`
- Italian: `it_IT`
- Portuguese (Brazil): `pt_BR`
- Chinese (Simplified): `zh_CN`
- Korean: `ko_KR`
- Russian: `ru_RU`

## Testing Translations

1. Go to **Settings > General** in WordPress
2. Set **Site Language** to your translated language
3. Visit a post with the like button
4. Check the admin settings page: **Settings > Like System**
5. Verify all strings are translated correctly

## Contributing

If you create a translation, please consider contributing it:

1. Fork the repository
2. Add your translation files
3. Submit a pull request
4. Help other users in your language!

## Support

For translation issues or questions:
- GitHub: https://github.com/binjuhor/wplp
- WordPress Support: https://wordpress.org/support/plugin/wplp
