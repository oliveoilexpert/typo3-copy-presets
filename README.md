# TYPO3 Copy Presets Extension

Allows editors to quickly paste content elements from a list of "copy presets" in the page layout module.

## Features

- 📋 **Quick Content Copying**: Add a "Paste preset" button next to every "Create new content" button in the page module
- 📁 **Organized Presets**: Group presets by page for easy organization
- 🎨 **Wizard Interface**: Familiar wizard UI similar to the new content element wizard
- 📦 **Container Support**: Full support for b13/container - copies containers with all children
- ✅ **Proper Copying**: Uses TYPO3 DataHandler for correct copying including FAL references and IRRE relations

## Requirements

- TYPO3 v13.4 or higher

## Installation

**Place the extension** in `typo3conf/ext/copy_presets/` or install via Composer (if packaged)

## Usage

### Setting up Copy Presets

1. **Create a Copy Preset Page**:
    - Create a new page in your page tree
    - Set the page type to "Copy Preset Page" (doktype 3151625)
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

## How It Works

1. **Button Injection**: The `PageLayoutButtonListener` adds buttons via JavaScript next to each "Create new content" button

2. **Preset Discovery**: The `CopyPresetService` finds all pages with `doktype = 3151625` and retrieves their content elements

3. **Wizard Display**: The controller renders a wizard showing presets grouped by their parent page

4. **Copying**: When a preset is selected, `DataHandler->copyRecord()` is used to properly copy the content element with all relations


## Container and Content Defender Extension Support

The extension automatically supports the b13/container extension because it uses TYPO3's DataHandler for copying. This ensures:
- Container elements are copied with all their children
- Nested containers work correctly
- All references and relations are maintained

Rules set by Content Defender are also respected in the wizard list.

## Troubleshooting

### Button doesn't appear
- Clear all caches
- Check browser console for JavaScript errors
- Verify the extension is activated

### No presets show in wizard
- Create at least one page with `doktype = 3151625`
- Add content elements to that page
- Clear caches

### Copy doesn't work
- Check backend user has permissions to create content on the target page
- Check for errors in TYPO3 backend logs

## License

MIT
