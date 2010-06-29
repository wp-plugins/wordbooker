=== Wordbooker ===

Contributors: SteveAtty
Tags: facebook, minifeed, newsfeed, crosspost, WPMU, Facebook Share, Facebook Like, social media
Requires at least: 2.8
Tested up to: 3.0
Stable tag: 1.8.7

This plugin allows you to cross-post your blog posts to your Facebook Wall. 

== Description ==

This plugin allows you to cross-post your blog posts to your Facebook Wall. You can also "cross polinate" comments between Facebook and your Wordpress blog.

Various options including "attribute" lines and polling for comments and automatic re-posting on edit can be configured.

NOTE : You have to have PHP V5 installed for this plugin to work as the code contains several PHP V5 specific features.


== Installation ==

1. [Download] (http://wordpress.org/extend/plugins/wordbooker/) the ZIP file.
1. Unzip the ZIP file.
1. Upload the `wordbooker` directory to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Navigate to `Options` &rarr; `Wordbooker` for configuration and follow the on-screen prompts.

== IMPORTANT ==  

Wordbooker 1.8 uses the new Faceobok OAUTH authorisation method. This means that when you upgrade you may find that you loose your "Session" with Facebook and you only get a partial option screen displayed. If this happens then simply reload the Wordbooker Options page and follow the instructions.


== Features ==

For more details on the various features please read the additional Features.txt file or check the [wordbooker](http://blogs.canalplan.org.uk/steve/category/wordbooker/) category on my blog which will contain information on the current and planned features list.

- Works with a complementary [Facebook application](http://www.facebook.com/apps/application.php?id=254577506873) to update your Facebook Wall and friends' News Feeds about your blog and page postings.
- Supports multi-author blogs: each blog author notifies only their own friends of their blog/page posts.
- Features a sidebar widget to display your current Facebook Status and picture. Multiple widgets can be supported in one single blog.
- Features a sidebar widget to display a "Fan"/Like box for any of your pages. Multiple widgets can be supported in one single blog.
- Features a Facebook Like Button which can be customised as to where it appears in your blog.
- Supports posting of Comments from your blog to the corresponsding Facebook wall article.
- Supports the pulling of comments FROM blogs posted to your Facebook wall, back into your blog. 
- Supports the posting of blog posts to Fan Pages (if you are an administrator of that page). This is currently experimental and there is bug in the API


== Frequently Asked Questions ==

= Isn't Wordbooker the same as importing my blog posts into Facebook Notes? =

It is certainly similar, but not the same:

- Facebook Notes imports and caches your blog posts (e.g., it subscribes to your blog's RSS feed).

Wordbooker uses the Facebook API to actively update your Facebook Wall just as if you had posted an update yourself on facebook.com. It also means that you can make changes to your blog postings *after* initially publishing them.

- With Wordbooker, your blog postings will have their own space in your Facebook Wall - just as if you'd written directly on to the wall yourself.

- Your updates will show up with a nifty WordPress logo next to them instead of the normal "Notes" icon, plus a link back to the full entry on your blog.


= How is this different from the WordPress application? =

The [WordPress application](http://www.facebook.com/apps/application.php?id=2373049596) allows you to post to your [wordpress.com](http://www.wordpress.com/) blog directly from within Facebook. You cannot use the Facebook app with a self-hosted WordPress blog.

This Wordbook plugin works in the reverse direction. When you publish a new post or page, the plugin, in conjunction with the [Wordbooker](http://www.facebook.com/apps/application.php?id=254577506873) Facebook application, cross-posts your new blog entry to your Facebook account. You cannot use Wordbooker with a blog hosted at wordpress.com.


= Why doesn't the Facebook Like / Facebook Share show up properly even though I've enabled it?

You may need to add the following to the HMTL tag in your theme : xmlns:fb="http://www.facebook.com/2008/fbml".
So it looks something like :  <html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:fb="http://www.facebook.com/2008/fbml">


= Why aren't my blog posts showing up in Facebook? =

- Wordbooker will not publish password-protected posts.

- Any errors Wordbooker encounters while communicating with Facebook will be recorded in error logs; the error logs (if any) are viewable in the "Wordbooker" panel of the "Options" WordPress admin page.

- To discourage spammy behavior, Facebook restricts each user of any application to approximately 25 posts within any rolling 24-hour window of time. If you've been playing around with Wordbooker and posting lots of test posts, you have likely hit this limit; it will appear in the error logs as `error_code 4: "Application request limit reached"`. There is nothing to do but wait it out.

- Facebook sometimes incorrectly returns this result to application requests (other developers have also reported this problem with their Facebook apps; it's not just Wordbook); there is also nothing the Wordbook plugin can do about this.



= My WordPress database doesn't use the default 'wp_' table prefix. Will this plugin still work? =

Yes, and its also WPMU compliant.



= How do I reset my Wordbooker/WordPress configuration so I can start over from scratch? =

1. Click the "Reset configuration" button in the "Wordbooker" panel of the "Options" WordPress admin page.
1. Deactivate the Wordbook plugin from your WordPress installation.
1. [Uninstall Wordbooker](http://www.facebook.com/apps/application.php?id=254577506873) from your Facebook account.
1. Download the [latest version](http://wordpress.org/extend/plugins/wordbooker/)
1. Re-install and re-activate the plugin.


= What is the Enable Extended description for Share Link option do? =

If you're using the Share action link on your posts to Facebook it uses the META DESCRIPTION tag to extract something from your post. If you dont have an SEO system which populates this, or if you dont usally use post excerpts then selecting this option populates the tag with the first couple hundred characters of your post which gives a nice block of text in the post that will appear when people share your post on their wall.



= How do I report problems or submit feature requests? =

- Use the [Wordbooker Discussion Board](http://www.facebook.com/apps/application.php?v=app_2373072738&id=254577506873). Either start a new topic, or add to an existing topic.

- Do *not* use the Review Wall for support or feature requests. People are unable to respond to Review Wall posts; you are less likely to get a response.

Alternatively, leave a comment on [my blog](http://blogs.canalplan.org.uk/steve).


== KNOWN CONFLICTS ==

If you have [Facebook Connect](http://wordpress.org/extend/plugins/wp-facebookconnect/) Version 1.2.1 installed then some features of Wordbooker will not work as expected. This is due to the Facebook Platform files shipped in Facebook Connect being out of date (as of 28-Feb-2010). To resolve this issue copy ALL the files in the /wordbooker/facebook-platform/php folder into the /wp-facebookconnect/facebook-client folder.


== Screenshots ==

1. Wordbooker Options/Configuration : Blog Level options
1. Wordbooker Options/Configuration : User Level options
2. Wordbooker Options : Overrides when posting


== Upgrade Notice ==

If you've upgrading from my forked version of Robert's Wordbook plugin ( http://wordpress.org/extend/plugins/wordbook ) then you'll have to remove the old version first as it's a new application and uses different API keys. If you dont want to lose track of any posts you've published with my version then BEFORE you de-install, export the wordbook_postcomments table. Then remove the old app, install this one, set up your configuration and then import the wordbook_postcomments table.

If you are upgrading from an earlier version of wordbooker then DO NOT deactivate the plugin before you upgrade as this will remove the tables. Simply delete the contents of the wordbooker folder and upload the new version.




== Changelog ==

= Version 1.8.8 : 29/06/2010 =
- Fix ANOTHER mistake caused by SVN problems - 

= Version 1.8.7 : 29/06/2010 =
- Fix a mistake caused by SVN problems -

= Version 1.8.6 : 29/06/2010 =
- Added support for Iframe Like/Share buttons.
- Added Faceboox "Fan"/ Like Multi-widget.
- Fixed problem with Graph API classes clashing with other Facebook related plugins.
- Fixed a bug in the image handling functions.
- Fixed a bug where selecting "show FB like / Share" buttons but not selecting where to show them hid the post.
- Changed a couple of class definitions to fix clashes with other Facebook related plugins.



= Version 1.8.5 : 20/06/2010 =
- Changed the json_decode handling in the Facebook GRAPH API to remove dependencies on Json 1.2.1
- Added Language support for FB Like button (uses the WPLANG constant).
- Added support for sharing main blog url on multiple post pages.
- Changed Support information list.
- Added "fallback" code for PHP versions missing json_decode.
- Put in checks for multibyte support.
- Changed format of "Next Scheduled Poll" text to work round timezone casting issues.
- Supress og:/fb: tags and FB javascript when share/like options are turned off.
- Wrapped og: and fb: header tags in htmlspecialchars to fix text "escaping" into page.
- Changed tag stripping code for extracts.
- Fixed 404 page throwing a Database error.
- Changed callback URLs from blog_info('url') to blog_info('wpurl') to fix issues with non "home directory" WP installs.
- Added more error trapping round user cache handling.
- Wrapped all array_keys calls in is_array checks.
- Added options to allow Share and Like buttons to be located either above or below post.


= Version 1.8.4 : 10/06/2010 =
- Fixed problems with the auth process.
- Added more diagnostic checks.
- Changed Facebook core call from CURL to old method.
- Added support for Facebook Like Button.
- Added support for Facebook Share Button
- Added a "Recent Facebook Activity" section to the Wordbooker Options page.
- Added checks for CURL and json_decode and tidied up error reporting.
- Fixed bug with json_decode for PHP < 5.2
- Fixed bug in error logging.
- Fixed missing tag (sometimes broke login URL in IE)
- Added code to fix "signature" issues during authentication.


= Version 1.8.3 : 01/06/2010 (Limited release via Wordbooker Page) =
- Changes rolled up into 1.8.4

= Version 1.8.2  : 30/05/2010 (Limited release via Wordbooker Page) =
- Changes rolled up into 1.8.3


= Version 1.8.1 : 30/05/2010 = 
- Fix issue with new API file directory.

= Version 1.8 : 30/05/2010 =
- Facebook authorisation migrated to OAUTH2
- Minor bug fixes
- Userguide updated to include new features and authorisation process.
- Recoded cron code to try to work round Facebook Database issues.



= Version 1.7.9 : 18/05/2010 =
- Several minor bug fixes and tweaks.
- Ability to use the User generated Post Excerpt to post to the wall.
 

= Version 1.7.8 :14/04/2010 =
- Fixed Scheduled post handling which was broken again.
- Recoded Advanced Diagnostics so they no longer slow down the posting process.
- Fixed bug concerning incorrect facebook IDs when posting comments.
- Recoded "non wordbook user" handing code
- Added more diagnostics



= Version 1.7.7 :05/04/2010 =
- Added support for WP thumbnails (needs WP/WPMU 2.9 or above).
- Added support for 'Quick Press' (i.e. post from Dashboard)
- Added support for use by non wordbooker users - posts from non wordbooker user inherit the blog level settings (and if the default user is set to a specific user they inherit those settings too)
- Added support for posts made by wp-o-matic.
- Added NextGen tags to the misbalanced tags list so they get stripped from the text of the excerpt.
- Added a lot more diagnostic messages.
- Fixed 'Press This' functionalilty
- Fixed loss of user settings on remote posts.
- Added more diagnostics
- Separated Errors and Diagnostics into two display blocks
- Advanced Diagnostics now a blog level option on the Options page rather than editing the PHP file



= Version 1.7.6 :  Limited release on Wordbooker Facebook page. =
- Changes for this release have been rolled up into 1.7.7.


= Version 1.7.5 : 13/03/2010 =
- Fixed a bug which stopped Scheduled Posts being pushed up to Facebook
- Added support for the "Excerpt" Box in the Add Post. If this is populated and the "Share" link option is enabled then the Excerpt text will be used to populate the Share Link.
- Added some addtional advanced debug coding.


= Version 1.7.4 : 12/03/2010 =
- Fix bug with option checking on new install which sometimes caused odd errors.


= Version 1.7.3	: 10/03/2010 =
-  Fix bitwise logic bug in permissions check code
-  Recode Status Cache update code to handle completely empty Facebook Statuses
-  Recode Missing Auths check to isolate it from Facebook Multi-query handling
-  Added more diagnostic messages to the advanced debugging process.

= Version 1.7.2 :  08/03/2010 =
 - Added user data caching
 - Multiple Facebook Status Widgets (Needs Wordpress 2.8 or above)
 - Recoding of Blog Level settings form
 - User level options added
 - Fixed bug in layout of Fan Page selector
 - Fan Page permission check/authorise added
 - General Facebook permission checks/authorise tidied up
 - Tidied up options page layout for various browsers.
 - Videos and images inserted using Viper-Video Quicktags and Shashin now handled properly and tags stripped from output.
 - Enhanced checks for conflicting (older) Facebook Platform files.
 - Added active plugins list to Support Diagnostics list.
 - Changed names of all wordbooker functions to avoid plugin conflicts.
 - Options set for each post are stored and recalled when posts are edited
 - Removed support for Facebook Profile Box posting (depreciated by Facebook)
 - Added ability for user to hide their FB status on the Wordbooker Admin page.
 - Widgets can display your profile or the pic/status from any of your Fan pages.
 - Apostrophes in page names no longer break things.
 - Added stripping of "extra" images inserted by plugins 
 - Publishing using the Press It book marklet picks up user preferences.
 - Revised Security hash coding for option forms.
 - Added User Guide and linked it from the Support section of the Plugin page.
 - Modified security check which locked contributors/subscribers out of blog
 - Blog Administrators are only people permitted to set blog level options
 - Where "post as specific user" is selected at blog level the user level options for that user are loaded at post creation time.
 - Added extended debug code to help troubleshoot problems.


= Version 1.6.1 :  29/01/2010 =
 - Fixed a bug in the Attribute "tag" handling.
 - Fixed the And/OR logic for publishing to Fan Page Walls
 - Publish to Fan Page Walls now working correctly.


= Version 1.6 :  22/01/2010 =
 - Added custom "tags" to Post Attribute and Status lines.
 - Added "Current logged in user" as an option for the target FB account
 - Added ability to choose to post to FB Wall, Fan Page Wall or both.
 - Fixed bug relating to extract length.
 - Added status_update permission check as this sometimes seems to fail.
 - Tidied up the handling of the options page for users with no wordbooker configuration


= Version 1.5  :  10/01/2010 =
 - Added check for "old" versions of the Facebook Client files which other plugins might be using. 
 - Further refinement of extract routine. 
 - User selectable "action link" for posts made to Facebook. 
 - Optional extended "description" meta tag creation for use with the "Share" action link. 
 - Fixes for issues with pluggable.php. 
 - Future posting now fully supported.
 - Fixes to multiple account configuration

= Version 1.4 :  05/01/2010 =
 - Modification of post extract routine to prevent incorrect truncation and character conversion.

= Version 1.3 :  03/01/2010 =
 - Removal of stray debugging code.
 - Tidy up and recoding of cron job.

= Version 1.2 :  02/01/2010 =
 - URL fixes, code tweaks.

= Version 1.1 :  02/01/201 =
 - Minor bug fix.
 
= version 1.0 :  02/01/2010 =
 - Base Release.


