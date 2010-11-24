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
<pre>
&#91;mendeley type="collection" id="xxx" groupby="xxx"&#93;
&#91;mendeley type="shared" id="xxx" groupby="xxx"&#93;
&#91;mendeley type="shared" id="xxx" groupby="xxx" filter="author=Michael Koch"&#93;
- the attribute "groupby" is optional; possible values currently are: "year"
</pre>

Additionally, there are widgets to display the content of collections or shared collections in widget areas of a theme.

The entries are formatted the following way - so, the style can be tailored using CSS. 
<pre>
    &lt;h2 class="wpmgrouptitle"&gt;grouptitle&lt;/h2&gt;
	&lt;p class="wpmref"&gt;
	   &lt;span class="wpmauthors"&gt;$authors&lt/span&gt;
	   &lt;span class="wpmyear"&gt;($year)&lt;/span&gt;: 
	   &lt;span class="wpmtitle"&gt;$title&lt;/span&gt;
	   , &lt;span class="wpmoutlet"&gt;$publication_outlet&lt;/span&gt;
	   &lt;span class="wpmvolume"&gt;$volume&lt;/span&gt;&lt;span class="wpmissue"&gt;($issue)&lt;/span&gt;
	   , &lt;span class="wpmeditors"&gt;$editors&lt;/span&gt;
	   , &lt;span class="wpmpages"&gt;$pages&lt;/span&gt;
	   , &lt;span class="wpmpublisher"&gt;$city: $publisher&lt;/span&gt;
	   , &lt;span class="wpmurl"&gt;$url&lt;/span&gt;
	&lt;/p&gt;
</pre>

The output in the widgets is formatted the following way:
<pre>
    &lt;ul class="wpmlist"&gt;
	&lt;li class="wpmlistref"&gt;
	title (if url is defined, then this title is linked to url)
	&lt;/li&gt;
	...
	&lt;/ul&gt;
</pre>

You can use the plugin in non widgetized themes, just try
<pre>
echo $mendeleyPlugin->formatSharedCollection(763, 10, array ('author' => 'Michael Koch'));
</pre>

For using the plugin you have to obtain an API key from Mendeley,
enter this Customer Key in the configuration section of the plugin,
and authorize the API. To do so the following steps have to be taken:
<ol>
<li> install plugin
<li> activate plugin
<li> get Customer Key and Customer Secret from http://dev.mendeley.com/
<li> enter the information in the wp-mendeley tab in the backend
<li> press "Get Access Key" on the wp-mendeley configuration page
<li> then you are redirected to the Mendeley web site to authorize the request, and redirected back to the blog
<li> now you can use shortcodes in your pages and blogs
</ol> 

== Installation ==

<ol>
<li> Upload archive contents to the `/wp-content/plugins/` directory
<li> Activate the plugin through the 'Plugins' menu in WordPress
<li> Configure your settings (especially enter Customer Key and Customer Secret obtained from Mendeley), and request Access Token
</ol>

<p>Please make sure that caching is switched on when accessing shared collections! There is currently an access rate limit of 150 requests per hour - and since we need one request for every document (for retrieving the details) this limit is reached quickly.</p>

<p>There are some reported problems with other plugins that are using the OAuth PHP library like tweetblender: If the other plugin does not check if the library is already loaded (as ours does), initializing the other plugins after wp_mendeley will result in an error message. In this case deactivate the other plugin.</p>

== Frequently Asked Questions ==

== Screenshots ==

== Change log ==

= 0.5.2 =
* corrected several bugs (that had to do with handling options)
* set caching to weekly as default option (due to rate limit restrictions of the api)

= 0.5.1 =
* corrected bug that used to overwrite access token with empty string after it was received and stored in database

= 0.5 =
* tested and debugged widget support
* provided widget support for non widgetized themes
* added functionality to filter for attributes in widget lists
* added functionality to filter for attributes in lists on pages (shortcode "mendeley")

= 0.4.1 =
* When displaying URLs, use different anchor texts for pdf, scribd, ...
* Load oauth library only when no other oauth library has been loaded before - to avoid a "Cannot redeclare class oauthconsumer" runtime error

= 0.4 =
* Support for additional document attributes (display journal issue, pages etc)
* Initial support for internationalization

= 0.3.1 (11.08.2010) =
* Corrected typo in source code
* More consistent and complete support for CSS formatting output
* Widgets now support display of latest / first x documents from collection

= 0.3.0 (11.08.2010) =
* Added support for caching the data requested from Mendeley in the Wordpress database

= 0.2.0 =
* Added support for widgets

= 0.1.0 =
* First release
