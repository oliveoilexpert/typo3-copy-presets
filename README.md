# TYPO3 Copy Presets Extension

Allows editors to quickly paste content elements from a list of "copy presets" in the page layout module.

## Features

- 📋 **Quick Content Copying**: Add a "Copy Preset" button next to every "Create new content" button in the page module
- 📁 **Organized Presets**: Group presets by page for easy organization
- 🎨 **Wizard Interface**: Familiar wizard UI similar to the new content element wizard
- 📦 **Container Support**: Full support for b13/container - copies containers with all children
- ✅ **Proper Copying**: Uses TYPO3 DataHandler for correct copying including FAL references and IRRE relations

## Requirements

- TYPO3 v13.0 or higher
- PHP 8.2 or higher

## Installation

1. **Place the extension** in `typo3conf/ext/copy_presets/` or install via Composer (if packaged)

2. **Activate the extension** in the Extension Manager

3. **Clear caches** in the Admin Tools

## Usage

### Setting up Copy Presets

1. **Create a Copy Preset Page**:
    - Create a new page in your page tree
    - Set the page type to "Copy Preset Page" (doktype 200)
    - Name it descriptively (e.g., "Marketing Templates", "Standard Sections")

2. **Add Content Elements**:
    - Add content elements to this page that you want to use as templates
    - These can be any content element type (text, images, containers, etc.)
    - The `header` field of each element will be used as the title in the wizard

3. **Organize Multiple Groups**:
    - Create multiple Copy Preset Pages to organize your templates
    - Each page becomes a separate tab/group in the wizard

### Using Copy Presets

1. Go to the **Web > Page** module
2. Next to any "Create new content" button, you'll see a **"Copy Preset"** button
3. Click it to open the wizard
4. Select the group/tab on the left
5. Click on the preset you want to copy
6. The element will be copied to the target position

## Technical Details

### New Page Type (doktype)

The extension adds a new page type with `doktype = 200`:
- **Name**: Copy Preset Page
- **Icon**: Document with copy symbol
- **Behavior**: Works like a normal page, visible in frontend

### Database Changes

No database changes required - uses standard TYPO3 tables and fields.

### File Structure

```
copy_presets/
├── Classes/
│   ├── Controller/
│   │   └── CopyPresetWizardController.php
│   ├── EventListener/
│   │   └── PageLayoutButtonListener.php
│   └── Service/
│       └── CopyPresetService.php
├── Configuration/
│   ├── Backend/
│   │   └── Routes.php
│   ├── TCA/
│   │   └── Overrides/
│   │       └── pages.php
│   ├── Icons.php
│   └── Services.yaml
├── Resources/
│   ├── Private/
│   │   ├── Language/
│   │   │   └── locallang_tca.xlf
│   │   └── Templates/
│   │       ├── Wizard.html
│   │       └── NoPresets.html
│   └── Public/
│       └── Icons/
│           ├── doktype-copypreset.svg
│           └── copy-preset-button.svg
├── ext_emconf.php
└── ext_localconf.php
```

## How It Works

1. **Button Injection**: The `PageLayoutButtonListener` adds buttons via JavaScript next to each "Create new content" button

2. **Preset Discovery**: The `CopyPresetService` finds all pages with `doktype = 200` and retrieves their content elements

3. **Wizard Display**: The controller renders a wizard showing presets grouped by their parent page

4. **Copying**: When a preset is selected, `DataHandler->copyRecord()` is used to properly copy the content element with all relations

## Container Extension Support

The extension automatically supports the b13/container extension because it uses TYPO3's DataHandler for copying. This ensures:
- Container elements are copied with all their children
- Nested containers work correctly
- All references and relations are maintained

## Customization

### Change the doktype number

Edit `Configuration/TCA/Overrides/pages.php` and change `200` to your preferred number.

### Customize the wizard appearance

Edit `Resources/Private/Templates/Wizard.html` to modify the wizard layout and styling.

### Add permissions

You can restrict which backend user groups can create Copy Preset Pages using standard TYPO3 page permissions.

## Troubleshooting

### Button doesn't appear
- Clear all caches
- Check browser console for JavaScript errors
- Verify the extension is activated

### No presets show in wizard
- Create at least one page with `doktype = 200`
- Add content elements to that page
- Clear caches

### Copy doesn't work
- Check backend user has permissions to create content on the target page
- Check for errors in TYPO3 backend logs

## License

GPL-2.0-or-later
