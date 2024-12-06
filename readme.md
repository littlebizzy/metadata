# Metadata

For custom titles and descriptions

## Changelog

### 1.0.0
- initial release
- generates "Metadata" meta box for all page/post types for custom Meta Title and Meta Description
- stores data in the `wp_postmeta` table using `_metadata_title` and `_metadata_description` keys per post
- always renders `<title>` and `<meta name="description"...` on all pages when activated
- if values not exist will fallback to use post titles and the first 160 characters of article content
- has a simple JS counter to let you know when length is within the optimal range of characters
- always appends ` - [SiteName]` to all <title> values in this initial release at least
- supports PHP 7.0 to PHP 8.3
- supports Multisite
