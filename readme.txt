=== Mendeley Plugin ===
Contributors: kochm
Donate link: http://www.kooperationssysteme.de/produkte/wpmendeleyplugin/
Tags: bibliography, mendeley
Requires at least: 2.8
Tested up to: 3.0
Stable tag: trunk

Mendeley Plugin for WordPress is a plugin for displaying information from the Mendeley "shared bibliography system" in WordPress blogs.

== Description ==

Mendeley Plugin for WordPress is a plugin for displaying information from the Mendeley "shared bibliography system" (www.mendeley.com) in WordPress blogs.

Using the public API from Mendeley, meta-information on documents in personal, public or shared collections is loaded and formatted as bibliographic entries.

The lists can be included in posts or pages using WordPress shortcodes:
&#91;mendeley type="collection" id="xxx" groupby="xxx"&#93;
&#91;mendeley type="shared" id="xxx" groupby="xxx"&#93;
- the attribute "groupby" is optional; possible values currently are: "year"

Additionally, there are very first versions widgets to display the content of collections or shared collections in widget areas of a theme.

The entries are formatted the following way - so, the style can be tailored using CSS. 
<pre>
	&lt;span class="wpmref"&gt;
	   &lt;span class="wpmauthors"&gt;$authors&lt/span&gt;
	   &lt;span class="wpmyear"&gt;($year)&lt;/span&gt;: 
	   &lt;span class="wpmtitle"&gt;$title&lt;/span&gt;
	   , &lt;span class="wpmoutlet"&gt;$publication_outlet&lt;/span&gt;
	   , &lt;span class="wpmurl"&gt;$url&lt;/span&gt;
	&lt;/span&gt;
</pre>

For using the plugin you have to obtain an API key from Mendeley,
enter this Customer Key in the configuration section of the plugin,
and authorize the API. To do so the following steps have to be taken:
1. install plubin
1. activate plugin
1. get Customer Key and Customer Secret from http://dev.mendeley.com/
1. enter the information in the wp-mendeley tab in the backend
1. press "Get Access Key" on the wp-mendeley configuration page
1. then you are redirected to the Mendeley web site to authorize the request, and redirected back to the blog
1. now you can use shortcodes in your pages and blogs 

== Installation ==

1. Upload archive contents to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Configure your settings (especially enter Customer Key and Customer Secret obtained from Mendeley), and request Access Token

== Frequently Asked Questions ==

== Screenshots ==

== Change log ==

= 0.3.0 =
* Added support for caching the date requested from Mendeley in the Wordpress database

= 0.2.0 =
* Added support for widgets

= 0.1.0 =
* First release
