=== Wordbooker ===

Contributors: SteveAtty
Tags: facebook, minifeed, newsfeed, crosspost, WPMU
Requires at least: 2.7
Tested up to: 2.9.1
Stable tag: 1.5

This plugin allows you to cross-post your blog posts to your Facebook Wall. 

== Description ==

This plugin allows you to cross-post your blog posts to your Facebook Wall. You can also "cross polinate" comments between Facebook and your Wordpres blog.

Various options including "attribute" lines and polling for comments and automatic re-posting on edit can be configured.


== Installation ==

1. [Download](http://wordpress.org/extend/plugins/wordbooker/) the ZIP file.
1. Unzip the ZIP file.
1. Upload the `wordbooker` directory to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Navigate to `Options` &rarr; `Wordbooker` for configuration and follow the on-screen prompts.


== Features ==

For more details on the various features please read the additional Features.txt file or check the [wordbooker](http://blogs.canalplan.org.uk/steve/category/wordbooker/) category on my blog which will contain information on the current and planned features list.

- Works with a complementary [Facebook application](http://www.facebook.com/apps/application.php?id=254577506873) to update your Facebook Wall and friends' News Feeds about your blog and page postings.
- Supports multi-author blogs: each blog author notifies only their own friends of their blog/page posts.
- Supports posting of Comments from your blog to the corresponsding Facebook wall article.
- Supports the pulling of comments FROM blogs posted to your Facebook wall, back into your blog. 
- Supports the posting of blog posts to Fan Pages (if you are an administrator of that page). This is currently experimental and there is bug in the API


== Frequently Asked Questions ==

= Isn't Wordbooker the same as importing my blog posts into Facebook Notes? =

It is certainly similar, but not the same:

- Facebook Notes imports and caches your blog posts (e.g., it subscribes to your blog's RSS feed).

Wordbooker uses the Facebook API to actively update your Facebook Wall just as if you had posted an update yourself on facebook.com. This means that your updates are likely to appear faster than waiting around for the Facebook Notes RSS reader to notice your update. It also means that you can make changes to your blog postings *after* initially publishing them.

- With Wordbooker, your blog postings will have their own space in your Facebook profile, instead of having to compete for space with your other posted Facebook Notes.

- Your updates will show up with a nifty WordPress logo next to them instead of the normal "Notes" icon :).

- Your Facebook Notes' comments will not show up on your WordPress blog. Wordbooker links everything back to your blog, so your comments will also stay on your blog 



= How is this different from the WordPress application? =

The [WordPress application](http://www.facebook.com/apps/application.php?id=2373049596) allows you to post to your [wordpress.com](http://www.wordpress.com/) blog directly from within Facebook. You cannot use the Facebook app with a self-hosted WordPress blog.

This Wordbook plugin works in the reverse direction. When you publish a new post or page, the plugin, in conjunction with the [Wordbooker](http://www.facebook.com/apps/application.php?id=254577506873) Facebook application, cross-posts your new blog entry to your Facebook account. You cannot use Wordbooker with a blog hosted at wordpress.com.



= Why aren't my blog posts showing up in Facebook? =

- Wordbooker will not publish password-protected posts.

- Any errors Wordbooker encounters while communicating with Facebook will be recorded in error logs; the error logs (if any) are viewable in the "Wordbooker" panel of the "Options" WordPress admin page.

- To discourage spammy behavior, Facebook restricts each user of any application to 10 posts within any rolling 48-hour window of time. If you've been playing around with Wordbooker and posting lots of test posts, you have likely hit this limit; it will appear in the error logs as `error_code 4: "Application request limit reached"`. There is nothing to do but wait it out.

- Facebook sometimes incorrectly returns this result to application requests (other developers have also reported this problem with their Facebook apps; it's not just Wordbook); there is also nothing the Wordbook plugin can do about this.



= My WordPress database doesn't use the default 'wp_' table prefix. Will this plugin still work? =

Yes, and its also WPMU compliant.



= How do I reset my Wordbooker/WordPress configuration so I can start over from scratch? =

1. Click the "Reset configuration" button in the "Wordbooker" panel of the "Options" WordPress admin page.
1. Deactivate the Wordbook plugin from your WordPress installation.
1. [Uninstall Wordbooker](http://www.facebook.com/apps/application.php?id=254577506873) from your Facebook account.
1. Download the [latest version](http://wordpress.org/extend/plugins/wordbooker/)
1. Re-install and re-activate the plugin.


= What is the Enable Extended description for Share This Link option do? =

If you're using the Share This action link on your posts to Facebook it uses the META DESCRIPTION tag to extract something from your post. If you dont have an SEO system which populates this, or if you dont usally use post excerpts then selecting this option populates the tag with the first couple hundred characters of your post.



= How do I report problems or submit feature requests? =

- Use the [Wordbooker Discussion Board](http://www.facebook.com/apps/application.php?v=app_2373072738&id=254577506873). Either start a new topic, or add to an existing topic.

- Do *not* use the Review Wall for support or feature requests. People are unable to respond to Review Wall posts; you are less likely to get a response.

Alternatively, leave a comment on [my blog](http://blogs.canalplan.org.uk/steve).


== Screenshots ==

1. Wordbooker Options/Configuration Screen
2. Wordbooker Options overrides when posting


== Upgrade Notice ==

If you've upgrading from my forked version of Robert's  Wordbook plugin ( http://wordpress.org/extend/plugins/wordbook ) then you'll have to remove the old version first as it's a new application and uses different API keys. If you dont want to lose track of any posts you've published with my version then BEFORE you de-install, export the wordbook_postcomments table. Then remove the old app, install this one, set up your configuration and then import the wordbook_postcomments table.

If you are upgrading from an earlier version of wordbooker then DO NOT deactivate the plugin before you upgrade as this will remove the tables. Simply delete the contents of the wordbooker folder and upload the new version.


== Changelog == 

= Version 1.5  :  06/01/2010 =
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


