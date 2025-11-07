# TYPO3 Copy Presets Extension

Allows editors to quickly copy content elements from a list of "copy presets" in the page layout module.

## Features

- 📋 **Quick Content Copying**: Add a "Copy preset" button next to every "Create new content" button in the page module
- 📁 **Organized Presets**: Group presets by page for easy organization
- 🎨 **Wizard Interface**: Familiar wizard UI similar to the new content element wizard
- 📦 **Container Support**: Full support for b13/container - copies containers with all children
- 🛡️ **Content Defender Compatibility**: Respects Content Defender rules when displaying presets
- ✅ **Proper Copying**: Uses TYPO3 DataHandler for correct copying including FAL references and IRRE relations

## Requirements

- TYPO3 v13.4 or higher

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
