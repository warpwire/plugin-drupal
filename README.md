# Warpwire Drupal Plugin

This is the latest version of the Warpwire Drupal plugin, compatible with Drupal 10+.

For earlier versions of Drupal, please see our [Legacy Drupal plugin](https://github.com/warpwire/plugin-drupal-legacy).

## Overview

The Warpwire Drupal plugin allows you to easily embed Warpwire media in your Drupal site.

The plugin extends the Drupal Media module, adding a new "Warpwire Media" Media Source. To insert
a Warpwire media item into your content, simply create a new instance of Warpwire Media in your
media library and insert it into your content like any other media item.


## Installation

### Requirements
- Drupal 10+
- Core Media module enabled
- Core Media Library module enabled
- Image library on server (ImageMagick or GD) have JPEG support enabled

### Installation Steps
- Download the latest release from the [releases page](https://github.com/warpwire/plugin-drupal/releases).
- Extract the contents of the release into your Drupal site's modules directory.
- Enable the Warpwire module in the Drupal admin interface.

### Configuration
On the following administration pages, you will need to update settings as follows:
- Administration -> Configuration -> Content authoring -> Text formats and editors
    - Configure the "Full HTML" format
        - Enable the "Embed media" filter.
        - If you have Warpwire content embedded in your site from a prior version of the plugin,
        enable "Warpwire Filter (legacy support)" to render that content.
        - For ease of inserting media, drag the "Drupal Media" button into your active toolbar.
- Administration -> Configuration -> Warpwire Media -> LTI launch settings
    - Enter your Warpwire site's URL, [LTI key, and LTI secret](https://warpwire.com/support/admin/external-keys/#lti).
    - Configure the institution and group name if desired.
- Administration -> Configuration -> Warpwire Media -> Default display settings
    - Change any defaults about how you want Warpwire content to appear in Drupal.


## Usage

### Adding Warpwire media to Drupal

To add a new Warpwire Media item to Drupal, follow these steps:
1. Within your Warpwire VOD account (external to Drupal), locate the media item you want to add to Drupal.
2. On the asset, click the "..." menu and select "Share"
3. Make sure "Share Link" is selected at the top of the window
4. Configure any custom settings you want to apply to the shared link using the checkboxes
   (these will override the defaults set above in the Drupal "Default display settings").
5. Determine permissions
    * If you would like the media to be viewed by anyone, including those not logged into Drupal
        * Select "Share with everyone on the web (Public)"
        * Copy the share URL, which will not include a signature
    * If you would like the media to be viewable by anyone that has the "LTI Launch Warpwire content" permission in Drupal
        * Select "Share with additional Users and Groups (Protected)"
        * Check the [Link Grants Access via LTI](https://warpwire.com/support/playback/url-shortcuts/#link-grants-access-lti) checkbox
        * Copy the share URL, which will include a nonce and signature
    * If you would like the media to be viewable only by specific users in Drupal
        * Select "Share with additional Users and Groups (Protected)"
        * Configure sharing within Warpwire VOD, using user accounts created by LTI launch from Drupal
        * Do not check the "Link Grants Access via LTI" checkbox
        * Copy the share URL, which will not include a signature
6. In Drupal, you can add the media from two locations:
    * Content -> Media -> Add media -> Warpwire media
    * In the Full HTML editor, click "Insert Media", then "Warpwire Media"
7. Paste the share URL into the "Warpwire Asset Share Link" field, add a title, and save the media item.