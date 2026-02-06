Integrate CiviCRM with Mattermost
=================================

**Contributors:** [needle](https://profiles.wordpress.org/needle/)<br/>
**Donate link:** [https://www.paypal.me/interactivist](https://www.paypal.me/interactivist)<br/>
**Tags:** civicrm, mattermost, sync<br/>
**Requires at least:** 5.3<br/>
**Tested up to:** 6.9<br/>
**Stable tag:** 1.0.0a<br/>
**License:** GPLv2 or later<br/>
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

Keeps Contacts in CiviCRM Groups in sync with Users in Mattermost Channels.



## Description

Please note: this is the development repository for *Integrate CiviCRM with Mattermost*.

*Integrate CiviCRM with Mattermost* is a WordPress plugin that keeps Contacts in CiviCRM Groups in sync with Users in Mattermost Channels.

### Requirements

This plugin requires a minimum of *WordPress 4.9* and *CiviCRM 5.80*. It also requires an install of Mattermost to interact with.

### Notes

**Please note:** This plugin is still in early stages of development. Use at your own risk.



## Installation

There are two ways to install from GitHub:

### ZIP Download

If you have downloaded *Integrate CiviCRM with Mattermost* as a ZIP file from the git repository, do the following to install and activate the plugin:

1. Unzip the .zip file and, if needed, rename the enclosing folder so that the plugin's files are located directly inside `/wp-content/plugins/wpcv-civicrm-mattermost`
2. Activate the plugin
3. Configure the plugin on the settings page
4. You are done!

### git clone

If you have cloned the code from the git repository, it is assumed that you know what you're doing.



## Usage

### Contact "Action Menu" item

This plugin provides an item in the "Action Menu" on the View Contact screen in CiviCRM. If the Contact does not have a Mattermost User, it will read "Create Mattermost User" and if not, it will read "Deactivate Mattermost User".

When creating a Mattermost User, if a Mattermost User already exists with the Name and Email of the CiviCRM Contact, then that Mattermost User will be linked to the Contact. You may also (optionally) add the User to a Mattermost Channel.

### CiviRules Actions

This plugin provides a number of CiviRules Actions that you can use to suit your purposes:

* Create a Mattermost User for a Contact
* Deactivate the Mattermost User for a Contact
* Add the Mattermost User for a Contact to a Mattermost Channel
* Remove the Mattermost User for a Contact from a Mattermost Channel

You are likely to want to apply the "Create a Mattermost User for a Contact" Action before applying "Add the Mattermost User for a Contact to a Mattermost Channel" Action. "Deactivate the Mattermost User for a Contact" and "Remove the Mattermost User for a Contact from a Mattermost Channel" can be used independently.

### CiviCRM Groups

To use this feature, you must enable it on the plugin's settings page.

When you create a Group in CiviCRM, you'll be able to select an option that creates a Channel in Mattermost to sync with. This allows you to add a Contact in CiviCRM to a Group and that Contact's Mattermost User will be added to the corresponding Mattermost Channel. Removing the Contact from the Group will remove them from the Mattermost Channel.



## Keeping your Groups and Channels in sync

There are three options that you can use to keep your Groups and Channels in sync:

1. A pair of WP-CLI commands
1. A WordPress scheduled event
1. A "Manual Sync" settings page

When using these options, you will need to choose your sync direction depending on whether your CiviCRM Groups or your Mattermost Channels are the "source of truth".

### WP-CLI commands

Use either `wp wpcvmm job sync-to-wp` or `wp wpcvmm job sync-to-civicrm` to sync all Groups and Channels each time the job runs.

### WordPress scheduled event

The plugin settings page enables you to set up a scheduled event to keep your CiviCRM Groups and Mattermost Channels in sync.

### "Manual Sync" page

Use the utilities on this page to sync your Groups and Channels immediately.
