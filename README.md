# Bulk Chapter Upload Plugin for Fictioneer

A WordPress plugin for the Fictioneer theme that allows you to bulk upload and schedule multiple chapters from a ZIP file.

---

# Changelog & Improvements In This Fork

## 1. **Core Functionality Enhancements**
- **Password Protection**
  - Added password protection for chapters with:
    - Password field (min 4 characters)
    - Password expiration date set autoset
    - Meta field `fictioneer_post_password_expiration_date`

- **Expiration System**
  - New "Expire Count" field to calculate expiration dates
  - Timezone-aware date calculations using site settings
  - Multiplier system: `days_to_add = expire_count * (chapter_number + 1)`

## 2. **Scheduling Improvements**
- **New in Schedule Type: Single Date**
  - Added delay minutes parameter for staggered publishing
  - Timezone conversion fixes using `DateTimeZone` handling
  - Expiry start date field for password expiration calculations

- **Enhanced Date Handling**
  - Fixed timezone issues by using `get_option('timezone_string')`
  - Added proper GMT conversion for scheduled dates
  - Better error handling for date parsing

## 3. **User Interface Additions**
- **New Form Fields**
  - Password field with strength requirements
  - Expire count input
  - Chapter categories multi-select
  - Delay minutes for single schedule type
  - Expiry start date picker

- **Improved Validation**
  - Better file type checking (strict ZIP validation)
  - Password length enforcement
  - Enhanced form field requirements

## 4. **Security Enhancements**
- Added `wp_kses_post` content sanitization
- Better nonce verification checks
- Stricter file upload validation
- Password field autocomplete="new-password"

## 5. **Error Handling & Logging**
- Detailed error messages for:
  - File read failures
  - ZIP extraction issues
  - Date parsing errors
  - Meta field updates
- Enhanced debug logging with `WP_DEBUG` support

## 6. **Category Management**
- Added multi-category selection
- Implemented `wp_set_post_categories()` for taxonomy handling
- Category selector with all available terms

## 7. **Code Structure Improvements**
- Better code organization with:
  - Separated functions for date handling
  - Clearer variable naming
  - More modular code structure
- Added cleanup functions for temp files

---
# Original Fork Readme

## Features

- Upload multiple chapters at once via a ZIP file
- Automatically link chapters to a selected story
- Choose between draft and published status
- Multiple scheduling options:
  - Single date (all chapters on same date)
  - Daily increment (one chapter per day)
  - Weekly schedule (specific days of the week)

## Requirements

- WordPress
- [Fictioneer Theme](https://github.com/Tetrakern/fictioneer)
- PHP 7.4 or higher
- ZIP extension enabled in PHP

## Installation

1. Download the plugin files
2. Upload to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress

## Usage

### Basic Upload

1. Go to "Bulk Chapter Upload" in your WordPress admin menu
2. Select the target story from the dropdown
3. Choose whether to publish chapters immediately or save as drafts
4. Select your ZIP file containing chapter text files
5. Click "Upload Chapters"

### File Requirements

- Files must be in .txt format
- File names will be used as chapter titles
- Each text file should contain the chapter content
- Files in the ZIP will be processed in alphabetical order

### Scheduling Options

#### No Scheduling
Chapters will be published immediately or saved as drafts based on your selection.

#### Single Date
All chapters will be scheduled for the same date and time.

1. Select "Single Date" as Schedule Type
2. Choose the desired date and time
3. All chapters will be scheduled for this time

#### Daily Increment
Chapters will be scheduled one day apart starting from your selected date.

1. Select "Daily Increment" as Schedule Type
2. Choose the start date and time
3. Each subsequent chapter will be scheduled 24 hours after the previous

#### Weekly Schedule
Chapters will be scheduled on specific days of the week.

1. Select "Weekly Schedule" as Schedule Type
2. Check the days you want chapters to be published
3. Set the publishing time
4. Chapters will be scheduled on the selected days at the specified time
  
### Debug Mode

To enable debug logging:

1. Add to wp-config.php:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

2. Check the debug.log file in wp-content for error messages

## Support

For issues and feature requests, please create an issue on the GitHub repository.

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Created for use with the Fictioneer theme by Tetrakern.
Created by @Sharnabeel 
