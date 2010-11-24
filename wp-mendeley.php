<?php
/*
Plugin Name: Mendeley Plugin
Plugin URI: http://www.kooperationssysteme.de/produkte/wpmendeleyplugin/
Version: 0.5.2

Author: Michael Koch
Author URI: http://www.kooperationssysteme.de/personen/koch/
License: http://www.opensource.org/licenses/mit-license.php
Description: This plugin offers the possibility to load lists of document references from Mendeley (shared) collections, and display them in WordPress posts or pages.
*/

/* 
The MIT License

Copyright (c) 2010 Michael Koch (email: michael.koch@acm.org)
 
Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

if (!class_exists("OAuthConsumer")) {
	require_once "oauth/OAuth.php"; 
}

define( 'REQUEST_TOKEN_ENDPOINT', 'http://www.mendeley.com/oauth/request_token/' );
define( 'ACCESS_TOKEN_ENDPOINT', 'http://www.mendeley.com/oauth/access_token/' );
define( 'AUTHORIZE_ENDPOINT', 'http://www.mendeley.com/oauth/authorize/' );
define( 'MENDELEY_OAPI_URL', 'http://www.mendeley.com/oapi/' );

define( 'PLUGIN_VERSION' , '0.5' );
define( 'PLUGIN_DB_VERSION', 1 );

// JSON services for PHP4
if (!function_exists('json_encode')) {
	include_once('json.php');
	$GLOBALS['JSON_OBJECT'] = new Services_JSON();
	function json_encode($value) {
		return $GLOBALS['JSON_OBJECT']->encode($value);
	}
	function json_decode($value) {
		return $GLOBALS['JSON_OBJECT']->decode($value);
	}
}

if (!class_exists("MendeleyPlugin")) {
	class MendeleyPlugin {
		var $adminOptionsName = "MendeleyPluginAdminOptions";
		protected $options = null;
		protected $consumer = null;
		protected $acctoken = null;
		protected $sign_method = null;
		function MendeleyPlugin() { // constructor
			$this->init();
		}
		function init() {
			$this->getOptions();
			$this->initializeDatabase();
			load_plugin_textdomain('wp-mendeley');
		}
		function sendAuthorizedRequest($url) {
			$this->getOptions();
			
			$request = OAuthRequest::from_consumer_and_token($this->consumer, $this->acctoken, 'GET', $url, array());
			$request->sign_request($this->sign_method, $this->consumer, $this->acctoken);

			// send request
			if ($this->settings['debug'] === 'true') {
				echo "<p>Request: ".$request->to_url()."</p>";
			}
			$resp = run_curl($request->to_url(), 'GET');
			if ($this->settings['debug'] === 'true') {
				echo "<p>Response:</p>";
				var_dump($resp);
			}

			$result = json_decode($resp);
			if ($result->error) {
				echo "<p>Mendeley Plugin Error: " . $result->error . "</p>";
			}
			return $result;
		}
		function processShortcode($attrs = NULL) {
			$type = $attrs['type'];
			$id = $attrs['id'];
			$groupby = $attrs['groupby'];
			$filter = $attrs['filter'];
			$filterattr = NULL;
			$filterval = NULL;
			if (isset($filter)) {
				if (strlen($filter)>0) {
					$filterarr = explode('=', $filter);
					$filterattr = $filterarr[0];
					if (isset($filterarr[1])) {
						$filterval = $filterarr[1];
					} else {
						$filterattr = NULL;
					}
				}
			}
			$result = "unknown type '$type'";
			if ($type === "collection") {
				$result = "";
				$res = $this->getCollection($id);
				$docarr = $this->loadDocs($res);
				if (isset($groupby)) {
					$docarr = $this->groupDocs($docarr, $groupby);
				}
				$currentgroupbyval = "";
				$groupbyval = "";
				foreach($docarr as $doc) {
					// check for filter
					if (!is_null($filterattr)) {
						$filtertrue = 0;
						if (strcmp($filterattr, 'author')==0) {
                                                	$author_arr = $doc->authors;
                                                	for($i = 0; $i < sizeof($author_arr); ++$i) {
                                                		if (stristr($author_arr[$i], $filterval) === FALSE) {
                                                			continue;
                                                		} else {
                                                			$filtertrue = 1;
                                                			break;
                                                		}
                                                	}
                                                } else {
                                                	// other attributes
                                                	if (strcmp($keyval, $doc->{$key})==0) {
                                                		$filtertrue = 1;
                                                	}
                                                }
						if ($filtertrue == 0) { continue; }
					}
					// check if groupby-value changed
					if ($groupby === "year") {
						$groupbyval = $doc->year;
					}
					if ($groupbyval != $currentgroupbyval) {
						$result = $result . '<h2 class="wpmgrouptitle">' . $groupbyval . '</h2>';
						$currentgroupbyval = $groupbyval;
					}
					$result = $result . '<p class="wpmref">' . $this->formatDocument($doc) . '</p>';
				}
			}
			if ($type === "shared") {
				$result = "";
				$res = $this->getSharedCollection($id);
				$docarr = $this->loadDocs($res);
				if (isset($groupby)) {
					$docarr = $this->groupDocs($docarr, $groupby);
				}
				$currentgroupbyval = "";
				$groupbyval = "";
				foreach($docarr as $doc) {
					// check for filter
					if (!is_null($filterattr)) {
						$filtertrue = 0;
						if (strcmp($filterattr, 'author')==0) {
                                                	$author_arr = $doc->authors;
                                                	for($i = 0; $i < sizeof($author_arr); ++$i) {
                                                		if (stristr($author_arr[$i], $filterval) === FALSE) {
                                                			continue;
                                                		} else {
                                                			$filtertrue = 1;
                                                			break;
                                                		}
                                                	}
                                                } else {
                                                	// other attributes
                                                	if (strcmp($keyval, $doc->{$key})==0) {
                                                		$filtertrue = 1;
                                                	}
                                                }
						if ($filtertrue == 0) { continue; }
					}
					// check if groupby-value changed
					if ($groupby === "year") {
						$groupbyval = $doc->year;
					}
					if ($groupbyval != $currentgroupbyval) {
						$result = $result . '<h2 class="wpmgrouptitle">' . $groupbyval . '</h2>';
						$currentgroupbyval = $groupbyval;
					}
					$result = $result . '<p class="wpmref">' . $this->formatDocument($doc) . "</p>";
				}
			}
			return $result;
		}
		
		/* get the ids of all documents in a Mendeley collection
		   and return them in an array */
		function getCollection($id) {
			if (is_null($id)) return NULL;
			// check cache
			$result = $this->getCollectionFromCache($id);
			if (!is_null($result)) {
				$doc_ids = $result->document_ids;
				return $doc_ids;
			}
			$url = MENDELEY_OAPI_URL . "library/collections/$id/?page=0&items=1000";
			$result = $this->sendAuthorizedRequest($url);
			$this->updateCollectionInCache($id, $result);
			$doc_ids = $result->document_ids;
			return $doc_ids;
		}
		/* get the ids of all documents in a Mendeley shared collection
		   and return them in an array */
		function getSharedCollection($id) {
			if (is_null($id)) return NULL;
			// check cache
			$result = $this->getSharedCollectionFromCache($id);
			if (!is_null($result)) {
				$doc_ids = $result->document_ids;
				return $doc_ids;
			}
			$url = MENDELEY_OAPI_URL . "library/sharedcollections/$id/?page=0&items=1000";
			$result = $this->sendAuthorizedRequest($url);
			$this->updateSharedCollectionInCache($id, $result);
			$doc_ids = $result->document_ids;
			return $doc_ids;
		}
		/* get all attributes (array) for a given document */
		function getDocument($docid) {
			if (is_null($docid)) return NULL;
			// check cache
			$result = $this->getDocumentFromCache($docid);
			if (!is_null($result)) return $result;
			$url = MENDELEY_OAPI_URL . "library/documents/$docid/";
			$result = $this->sendAuthorizedRequest($url);
			$this->updateDocumentInCache($docid, $result);
			return $result;
		}
		/* get the ids/names of all collections for the current user */
		function getCollections() {
			$url = MENDELEY_OAPI_URL . "library/collections/?items=1000";
			$result = $this->sendAuthorizedRequest($url);
			return $result;
		}
		/* get the ids/names of all shared collections for the current user */ 
		function getSharedCollections() {
			$url = MENDELEY_OAPI_URL . "library/sharedcollections/?items=1000";
			$result = $this->sendAuthorizedRequest($url);
			return $result;
		}
		/* get the meta information (array) for all document ids in
		   the array given as an input parameter */
		function loadDocs($docidarr, $count=0) {
			$res = array();
			if ($count == 0) { $count = sizeof($docidarr); }
			for($i=0; $i <= $count; $i++) {
				$docid = $docidarr[$i];
				$doc = $this->getDocument($docid);
				$res[] = $doc;
			}
			return $res;
		}

		/* sort the documents that have been loaded before using loadDocs */
		function xxsortDocs($docarr, $sortby) {
			usort($docarr, "cmpmendeleydoc");
		}
		function sortDocs($data, $sortby) {
			for ($i = count($data) - 1; $i >= 0; $i--) {
				$swapped = false;
				for ($j = 0; $j < $i; $j++) {
					if ( $data[$j]->year < $data[$j + 1]->year ) {
						$tmp = $data[$j];
                				$data[$j] = $data[$j + 1];
                				$data[$j + 1] = $tmp;
                				$swapped = true;
					}
				}
				if (!$swapped) {
					return $data;
				}
			}
			return $data;
		}
		
		/* group the documents that have been loaded before using loadDocs,
		   i.e. $docarr holds an array of meta information arrays, after
		   the function ran, the meta information arrays (document objects)
		   will be grouped according to the groupby parameter. */
		function groupDocs($docarr, $groupby) {
			// TBD: Currently "groupby" is ignored - grouping is always done by "year"
			$grpvalues = array();
			for($i=0; $i <= sizeof($docarr); $i++) {
				$doc = $docarr[$i];
				$grpval = $doc->year;
				if (isset($grpval)) {
					$grpvalues[$grpval][] = $doc;
				}
			}
			krsort($grpvalues);
			// linearize results
			$result = array();
			foreach ($grpvalues as $arr) {
				foreach ($arr as $doc) {
					$result[] = $doc;
				}
			}
			return $result;
		}
		
		/* produce the output for one document */
		/* the following attributes are available in the doc object
			title
			year
			authors*
			editors*
			tags*
			keywords*
			identifiers* (issn,...)
			url
			discipline*
			publication_outlet
			pages
			issue
			volume
			city
			publisher
			abstract
			// type: "Book Section", "Journal Article", "Generic", ...
		*/
		function formatDocument($doc) {
			$author_arr = $doc->authors;
			$authors = "";
			for($i = 0; $i < sizeof($author_arr); ++$i) {
				if ($i > 0) $authors = $authors.", ";
				$authors = $authors.$author_arr[$i];
			}
			$editor_arr = $doc->editors;
			$editors = "";
			if (isset($doc->editors)) {
				for($i = 0; $i < sizeof($editor_arr); ++$i) {
					if ($i > 0) $editors = $editors.", ";
					$editors = $editors.$editor_arr[$i];
				}
			}
			$tmps = '<span class="wpmauthors">' . $authors . '</span> ' .
			        '<span class="wpmyear">(' . $doc->year . ')</span>: ' . 
			        '<span class="wpmtitle">' . $doc->title . '</span>';
			if (isset($doc->publication_outlet)) {
				$tmps .= ', <span class="wpmoutlet">' . 
				    $doc->publication_outlet . '</span>';
			}
			if (isset($doc->volume)) {
				$tmps .= ' <span class="wpmvolume">' . $doc->volume . '</span>';
			}
			if (isset($doc->issue)) {
				$tmps .= '<span class="wpmissue">(' . $doc->issue . ')</span>';
			}
			if (isset($doc->editors)) {
				if (strlen($editors)>0) {
					$tmps .= ', <span class="wpmeditors">' . $editors . ' (' . __('ed.','wp-mendeley') . ')</span>';
				}
			}
			if (isset($doc->pages)) {
				$tmps .= ', <span class="wpmpages">' . __('p.','wp-mendeley') . ' ' . $doc->pages . '</span>';
			}
			if (isset($doc->publisher)) {
				if (isset($doc->city)) {
					$tmps .= ', <span class="wpmpublisher">' . $doc->city . ': ' . $doc->publisher . '</span>';
				} else {
					$tmps .= ', <span class="wpmpublisher">' . $doc->publisher . '</span>';
				}
			}
			if (isset($doc->url)) {
				// determine the text for the anchor
				$atext = "URL";
				if (endsWith($doc->url, "pdf", false)) { $atext = "PDF"; }
				if (endsWith($doc->url, "ps", false)) { $atext = "PS"; }
				if (endsWith($doc->url, "zip", false)) { $atext = "ZIP"; }
				if (startsWith($doc->url, "http://www.scribd.com", false)) { $atext = "Scribd"; }
				$tmps .= ', <span class="wpmurl"><a href="' . 
					$doc->url . '">' . $atext . '</a></span>';
			}
			return $tmps;
		}
		function formatDocumentShort($doc) {
			$tmps = '<span class="wpmtitle">';
			if (isset($doc->url)) {
				$tmps .= '<a href="' .  $doc->url . '">' . $doc->title . '</a>';
			} else {
				$tmps .= $doc->title;
			}
			$tmps .= '</span>';
			return $tmps;
		}

		/* create database tables for the caching functionality */
		/* database fields:
		     type = 0 (document), 1 (collection), 2 (shared collection)
		     mid = Mendeley id as string
		     time = timestamp
		*/
		function initializeDatabase() {
			global $wpdb;
			$table_name = $wpdb->prefix . "mendeleycache";
			// check for table: if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) 
			// if ($this->settings['db_version'] < PLUGIN_DB_VERSION) {
			if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
				$sql = "CREATE TABLE " . $table_name . " (
						  id mediumint(9) NOT NULL AUTO_INCREMENT,
						  type mediumint(9) NOT NULL,
						  mid tinytext NOT NULL,
						  content text,
						  time bigint(11) DEFAULT '0' NOT NULL,
						  UNIQUE KEY id (id)
						);";
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
				$this->settings['db_version'] = PLUGIN_DB_VERSION;
				update_option($this->adminOptionsName, $this->settings);
			}
		}
		/* check cache database */
		function getDocumentFromCache($docid) {
			global $wpdb;
			if ("$docid" === "") return NULL;
			if ($this->settings['cache_docs'] === "no") return NULL;
			$table_name = $wpdb->prefix . "mendeleycache";
			$dbdoc = $wpdb->get_row("SELECT * FROM $table_name WHERE type=0 AND mid=$docid");
			if ($dbdoc) {
				// check timestamp
				$delta = 3600000;
				if ($this->settings['cache_docs'] === "day") { $delta = 86400000; }
				if ($this->settings['cache_docs'] === "week") { $delta = 604800000; }
				if ($dbdoc->time + $delta > time()) {
					return json_decode($dbdoc->content);
				}
			}
			return NULL;
		}
		function getCollectionFromCache($cid) {
			global $wpdb;
			if ("$cid" === "") return NULL;
			if ($this->settings['cache_collections'] === "no") return NULL;
			$table_name = $wpdb->prefix . "mendeleycache";
			$dbdoc = $wpdb->get_row("SELECT * FROM $table_name WHERE type=1 AND mid=$cid");
			if ($dbdoc) {
				// check timestamp
				$delta = 3600000;
				if ($this->settings['cache_collections'] === "day") { $delta = 86400000; }
				if ($this->settings['cache_collections'] === "week") { $delta = 604800000; }
				if ($dbdoc->time + $delta > time()) {
					return json_decode($dbdoc->content);
				}
			}
			return NULL;
		}
		function getSharedCollectionFromCache($cid) {
			global $wpdb;
			if ("$cid" === "") return NULL;
			if ($this->settings['cache_collections'] === "no") return NULL;
			$table_name = $wpdb->prefix . "mendeleycache";
			$dbdoc = $wpdb->get_row("SELECT * FROM $table_name WHERE type=2 AND mid=$cid");
			if ($dbdoc) {
			// check timestamp
				$delta = 3600000;
				if ($this->settings['cache_collections'] === "day") { $delta = 86400000; }
				if ($this->settings['cache_collections'] === "week") { $delta = 604800000; }
				if ($dbdoc->time + $delta > time()) {
					return json_decode($dbdoc->content);
				}
			}
			return NULL;
		}
		/* add data to database */
		function updateDocumentInCache($docid, $doc) {
			global $wpdb;
			$table_name = $wpdb->prefix . "mendeleycache";
			$dbdoc = $wpdb->get_row("SELECT * FROM $table_name WHERE type=0 AND mid=$docid");
			if ($dbdoc) {
				$wpdb->update($table_name, array('time' => time(), 'content' => json_encode($doc)), array( 'type' => '0', 'mid' => "$docid"));
				return;
			}
			$wpdb->insert($table_name, array( 'type' => '0', 'time' => time(), 'mid' => strval($docid), 'content' => json_encode($doc)));
		}
		function updateCollectionInCache($cid, $doc) {
			global $wpdb;
			$table_name = $wpdb->prefix . "mendeleycache";
			$dbdoc = $wpdb->get_row("SELECT * FROM $table_name WHERE type=1 AND mid=$cid");
			if ($dbdoc) {
				$wpdb->update($table_name, array('time' => time(), 'content' => json_encode($doc)), array( 'type' => '1', 'mid' => "$cid"));
				return;
			}
			$wpdb->insert($table_name, array( 'type' => '1', 'time' => time(), 'mid' => strval($cid), 'content' => json_encode($doc)));
		}
		function updateSharedCollectionInCache($cid, $doc) {
			global $wpdb;
			$table_name = $wpdb->prefix . "mendeleycache";
			$dbdoc = $wpdb->get_row("SELECT * FROM $table_name WHERE type=2 AND mid=$cid");
			if ($dbdoc) {
				$wpdb->update($table_name, array('time' => time(), 'content' => json_encode($doc)), array( 'type' => '2', 'mid' => "$cid"));
				return;
			}
			$wpdb->insert($table_name, array( 'type' => '2', 'time' => time(), 'mid' => strval($cid), 'content' => json_encode($doc)));
		}

		function getOptions() {
			if ($this->settings != null)
				return $this->settings;
			$this->settings = array(
				'debug' => 'false',
				'cache_collections' => 'week',
				'cache_docs' => 'week',
				'consumer_key' => '',
				'consumer_secret' => '',
				'req_token' => '',
				'req_token_secret' => '',
				'access_token' => '',
				'access_token_secret' => '',
				'version' => PLUGIN_VERSION,
				'db_version' => 0 );
			$tmpoptions = get_option($this->adminOptionsName);
			if (!empty($tmpoptions)) {
				foreach ($tmpoptions as $key => $option)
					$this->settings[$key] = $option;
			}
			update_option($this->adminOptionsName, $this->settings);
			// initialize some variables
			$consumer_key = $this->settings['consumer_key'];
            		$consumer_secret = $this->settings['consumer_secret'];
            		$this->consumer = new OAuthConsumer($consumer_key, $consumer_secret, NULL);
			$this->sign_method = new OAuthSignatureMethod_HMAC_SHA1();
			$acc_token = $this->settings['access_token'];
			$acc_token_secret = $this->settings['access_token_secret'];
			$this->acctoken = new OAuthConsumer($acc_token, $acc_token_secret, NULL);
			
			return $this->settings;
		}
		function printAdminPage() {
			$this->getOptions();
			// check if any form data has been submitted and process it
			if (isset($_POST['update_mendeleyPlugin'])) {
				if (isset($_POST['debug'])) {
					$this->settings['debug'] = $_POST['debug'];
				}
				if (isset($_POST['cacheCollections'])) {
					$this->settings['cache_collections'] = $_POST['cacheCollections'];
				}
				if (isset($_POST['cacheDocs'])) {
					$this->settings['cache_docs'] = $_POST['cacheDocs'];
				}
				if (isset($_POST['consumerKey'])) {
					$this->settings['consumer_key'] = $_POST['consumerKey'];
				}
				if (isset($_POST['consumerSecret'])) {
					$this->settings['consumer_secret'] = $_POST['consumerSecret'];
				}
				update_option($this->adminOptionsName, $this->settings);
?>
<div class="updated"><p><strong><?php _e("Settings updated.", "MendeleyPlugin"); ?></strong></p></div>
<?php
			}
			// check if we should start a request_token, authorize request
			if (isset($_POST['request_mendeleyPlugin'])) {
				if (isset($_POST['consumerKey'])) {
					$this->settings['consumer_key'] = $_POST['consumerKey'];
				}
				if (isset($_POST['consumerSecret'])) {
					$this->settings['consumer_secret'] = $_POST['consumerSecret'];
				}
				update_option($this->adminOptionsName, $this->settings);
				$consumer_key = $this->settings['consumer_key'];
                		$consumer_secret = $this->settings['consumer_secret'];
                		$this->consumer = new OAuthConsumer($consumer_key, $consumer_secret, NULL);

				// sign request and get request token
				$params = array();
				$req_req = OAuthRequest::from_consumer_and_token($this->consumer, NULL, "GET", REQUEST_TOKEN_ENDPOINT, $params);
				$req_req->sign_request($this->sign_method, $this->consumer, NULL);
				$request_ret = run_curl($req_req->to_url(), 'GET');

				// if fetching request token was successful we should have oauth_token and oauth_token_secret
				$token = array();
				parse_str($request_ret, $token);
				$oauth_token = $token['oauth_token'];
				$this->settings['req_token'] = $token['oauth_token'];
				$this->settings['req_token_secret'] = $token['oauth_token_secret'];
				update_option($this->adminOptionsName, $this->settings);

				$domain = $_SERVER['HTTP_HOST'];
				$uri = $_SERVER["REQUEST_URI"];
				$callback_url = "http://$domain$uri&access_mendeleyPlugin=true";
				$auth_url = AUTHORIZE_ENDPOINT . "?oauth_token=$oauth_token&oauth_callback=".urlencode($callback_url);
				redirect($auth_url);
				exit;
			}
			// check if we should start a access_token request (callback)
			if (isset($_GET['access_mendeleyPlugin']) &&
				(strcmp($_GET['access_mendeleyPlugin'],'true')==0)) {
		
				$req_token = $this->settings['req_token'];
				$req_token_secret = $this->settings['req_token_secret'];
				$reqtoken = new OAuthConsumer($req_token, $req_token_secret, NULL);

				// exchange authenticated request token for access token
				$params = array('oauth_verifier' => $_GET['oauth_verifier']);
				$acc_req = OAuthRequest::from_consumer_and_token($this->consumer, $reqtoken, "GET", ACCESS_TOKEN_ENDPOINT, $params);
				$acc_req->sign_request($this->sign_method, $this->consumer, $reqtoken);
				$access_ret = run_curl($acc_req->to_url(), 'GET');

				// if access token fetch succeeded, we should have oauth_token and oauth_token_secret
				// parse and generate access consumer from values
				$token = array();
				parse_str($access_ret, $token);
				if (isset($token['oauth_token']) && (strlen(trim($token['oauth_token']))>0)) {
					$this->settings['access_token'] = $token['oauth_token'];
					$this->settings['access_token_secret'] = $token['oauth_token_secret'];
					$this->accesstoken = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret'], NULL);
					update_option($this->adminOptionsName, $this->settings);
?>
<div class="updated"><p><strong><?php _e("New Access Token retrieved.", "MendeleyPlugin"); ?></strong></p></div>
<?php
				}
			}
			// display the admin panel options
?>
<div class="wrap">
<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
<h2>Mendeley Plugin</h2>

This plugin offers the possibility to load lists of document references from Mendeley (shared) collections, and display them in WordPress posts or pages.

The lists can be included in posts or pages using WordPress shortcodes:

<ul>
<li>- [mendeley type="collection" id="xxx" groupby=""], groupby=year
<li>- [mendeley type="shared" id="xxx" groupby=""]
<li>- [mendeley type="shared" id="xxx" groupby="" filter=""], filter=ATTRNAME=AVALUE, e.g. author=Michael Koch
</ul>

<h3>Settings</h3>

<h4>Debug</h4>

<p><input type="radio" id="debug_yes" name="debug" value="true" <?php if ($this->settings['debug'] === "true") { _e(' checked="checked"', "MendeleyPlugin"); }?> /> Yes&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" id="debug_no" name="debug" value="false" <?php if ($this->settings['debug'] === "false") { _e(' checked="checked"', "MendeleyPlugin"); }?>/> No</p>
 
<h4>Caching</h4>

<p>
Cache collection requests
    <select name="cacheCollections" size="1">
      <option value="no" id="no" <?php if ($this->settings['cache_collections'] === "no") { echo(' selected="selected"'); }?>>no caching</option>
      <option value="week" id="week" <?php if ($this->settings['cache_collections'] === "week") { echo(' selected="selected"'); }?>>refresh weekly</option>
      <option value="day" id="day" <?php if ($this->settings['cache_collections'] === "day") { echo(' selected="selected"'); }?>>refresh daily</option>
      <option value="hour" id="hour" <?php if ($this->settings['cache_collections'] === "hour") { echo(' selected="selected"'); }?>>refresh hourly</option>
    </select><br/>
 Cache document requests
     <select name="cacheDocs" size="1">
      <option value="no" id="no" <?php if ($this->settings['cache_docs'] === "no") { echo(' selected="selected"'); }?>>no caching</option>
      <option value="week" id="day" <?php if ($this->settings['cache_docs'] === "week") { echo(' selected="selected"'); }?>>refresh weekly</option>
      <option value="day" id="day" <?php if ($this->settings['cache_docs'] === "day") { echo(' selected="selected"'); }?>>refresh daily</option>
      <option value="hour" id="hour" <?php if ($this->settings['cache_docs'] === "hour") { echo(' selected="selected"'); }?>>refresh hourly</option>
    </select><br/>
</p>

<p>To turn on caching is important, because Mendeley currently imposes a rate limit to requests to the service (currently 150 requests per hour - and we need one request for every single document details). See <a href="http://dev.mendeley.com/docs/rate-limiting">http://dev.mendeley.com/docs/rate-limiting</a> for more details on this restriction.</p>

<div class="submit">
<input type="submit" name="update_mendeleyPlugin" value="Update Settings">
</div>

<h3>Mendeley Collection IDs</h3>

<p>Currently, the plugin asks the user to specify the ids of Mendeley (shared) collections to display the documents in the collection. Pressing the button bellow will request and print the list of (shared) collections with the corresponding ids from the user account that authorized the access key to look up the ids you need.

<?php
			// check if we shall display (shared) collection information
			if (isset($_POST['request_mendeleyIds'])) {
				$result = $this->getCollections();
				echo "<h4>Collections</h4><ul>";
				if (is_array($result)) {
					for($i = 0; $i < sizeof($result); ++$i) {
						$c = $result[$i];
						echo '<li>' . $c->name . ', ' . $c->id;
					}
				}
				echo "</ul>";
				$result = $this->getSharedCollections();
				echo("<h4>Shared Collections</h4><ul>");
				if (is_array($result)) {
					for($i = 0; $i < sizeof($result); ++$i) {
						$c = $result[$i];
						echo '<li>' . $c->name . ', ' . $c->id;
					}
				}
				echo "</ul>";
			}
?>
<div class="submit">
<input type="submit" name="request_mendeleyIds" value="Request (Shared) Collection Ids">
</div>

<h3>API Keys</h3>

<p>The Mendeley Plugin uses the <a href="http://www.mendeley.com/oapi/methods/">Mendeley OpenAPI</a> to access
the information from Mendeley (Shared)Collections. For using this API you first need to request a Consumer Key
and Consumer Secret from Mendeley. These values have to be entered in the following two field. To request the key
and the secret go to <a href="http://dev.mendeley.com/">http://dev.mendeley.com/</a> and register a new application.</p>

<p>Mendeley API Consumer Key<br/>
<input type="text" name="consumerKey" value="<?php echo $this->settings['consumer_key']; ?>" size="60"></input></p>
<p>Mendeley API Consumer Secret<br/>
<input type="text" name="consumerSecret" value="<?php echo $this->settings['consumer_secret']; ?>" size="60"></input></p>

<p>Since Collections and SharedCollections are user-specific, the plugin needs to be authorized to access this 
information in the name of a particular user. The Mendeley API uses the OAuth protocol for doing this. 
When you press the button bellow, the plugin requests authorization from Mendeley. Therefore, you will be asked by
Mendeley to log in and to authorize the request from the login. As a result an Access Token will be generated
and stored in the plugin.</p>

<div class="submit">
<input type="submit" name="request_mendeleyPlugin" value="Request and Authorize Token">
</div>

<p>Mendeley API Request Token<br/>
<input type="text" readonly="readonly" name="token" value="<?php echo $this->settings['req_token']; ?>" size="60"></input></p>
<p>Mendeley API Request Token Secret<br/>
<input type="text" readonly="readonly" name="tokenSecret" value="<?php echo $this->settings['req_token_secret']; ?>" size="60"></input></p>
<p>Mendeley Access Token<br/>
<input type="text" readonly="readonly" name="accessToken" value="<?php echo $this->settings['access_token']; ?>" size="60"></input></p>
<p>Mendeley Access Token Secret<br/>
<input type="text" readonly="readonly" name="accessTokenSecret" value="<?php echo $this->settings['access_token_secret']; ?>" size="60"></input></p>
</form>
</div>
<?php
		}

/* functions to be used in non-widgetized themes instead of widgets */

		/* return formatted version of collection elements */
		function formatCollection($id, $maxdocs = 10, $filter = NULL) {
			if (is_null($id)) return '';
              		$result = '';
			$res = $this->getCollection($id);
			$docarr = $this->loadDocs($res);
			$docarr = $this->sortDocs($docarr, "year");
			$count = 0;
			foreach($docarr as $doc) {  
				if (!is_null($filter)) {
					$filtertrue = 0;
					foreach ($filter as $key => $keyval) {
						// special handling for authors
						if (strcmp($key, 'author')==0) {
							$author_arr = $doc->authors;
							for($i = 0; $i < sizeof($author_arr); ++$i) {
								if (stristr($author_arr[$i], $keyval) === FALSE) {
									continue;
								} else {
									$filtertrue = 1;
									break;
								}
							}
						} else {
						// other attributes
							if (strcmp($keyval, $doc->{$key})==0) {
								$filtertrue = 1;
							}
						}
						break; // just one filter now ...
					}
					if ($filtertrue < 1) {
						continue;
					}
				}
				$result .= '<li class="wpmlistref">' . $this->formatDocumentShort($doc) .  '</li>';
				$count++;
				if ($count > $maxdocs) break;
			}
			return $result;
		}

		/* return formatted version of shared collection elements */
		function formatSharedCollection($id, $maxdocs = 10, $filter = NULL) {
			if (is_null($id)) return '';
              		$result = '';
			$res = $this->getSharedCollection($id);
			$docarr = $this->loadDocs($res);
			$docarr = $this->sortDocs($docarr, "year");
			$count = 0;
			foreach($docarr as $doc) {  
				if (!is_null($filter)) {
					$filtertrue = 0;
					foreach ($filter as $key => $keyval) {
						// special handling for authors
						if (strcmp($key, 'author')==0) {
							$author_arr = $doc->authors;
							for($i = 0; $i < sizeof($author_arr); ++$i) {
								if (stristr($author_arr[$i], $keyval) === FALSE) {
									continue;
								} else {
									$filtertrue = 1;
									break;
								}
							}
						} else {
						// other attributes
							if (strcmp($keyval, $doc->{$key})==0) {
								$filtertrue = 1;
							}
						}
						break; // just one filter now ...
					}
					if ($filtertrue < 1) {
						continue;
					}
				}
				$result .= '<li class="wpmlistref">' . $this->formatDocumentShort($doc) .  '</li>';
				$count++;
				if ($count > $maxdocs) break;
			}
			return $result;
		}

	}
}

if (class_exists("MendeleyPlugin")) {
	$mendeleyPlugin = new MendeleyPlugin();
	function cmpmendeleydoc($a, $b) {
		if ($a->year == $b->year) {
			return 0;
		}
		return ($a->year < $b->year) ? -1 : 1;
	}
}
if (!function_exists("wp_mendeley_add_pages")) {
	function wp_mendeley_add_pages() {
		global $mendeleyPlugin;
		if (!isset($mendeleyPlugin)) {
			return;
		}
		if (function_exists('add_options_page')) {
			add_options_page('WP Mendeley', 'WP Mendeley', 8, basename(__FILE__), array(&$mendeleyPlugin,'printAdminPage'));
		}
	}
}
if (isset($mendeleyPlugin)) {
	// Actions
	add_action('wp-mendeley/wp-mendeley.php', array(&$mendeleyPlugin,'init'));
	add_action('admin_menu', 'wp_mendeley_add_pages');
	// Filters
	// Shortcodes
	add_shortcode('mendeley', array(&$mendeleyPlugin,'processShortcode'));
	add_shortcode('MENDELEY', array(&$mendeleyPlugin,'processShortcode'));
}


/**
 * MendeleyCollectionWidget Class
 */
class MendeleyCollectionWidget extends WP_Widget {
    /** constructor */
    function MendeleyCollectionWidget() {
        parent::WP_Widget(false, $name = 'Mendeley Collection');	
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {		
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        $cid = apply_filters('widget_cid', $instance['cid']);
        $maxdocs = apply_filters('widget_cid', $instance['count']);
        $filterattr = apply_filters('widget_cid', $instance['filterattr']);
        $filterval = apply_filters('widget_cid', $instance['filterval']);
        ?>
              <?php echo $before_widget; ?>
                  <?php if ( $title )
                        echo $before_title . $title . $after_title; ?>
              <?php
              		$result = '<ul class="wpmlist">';
			if (strlen($filterattr)<1) {
				$result .= $mendeleyPlugin->formatCollection($cid, $maxdocs);
			} else {
				$result .= $mendeleyPlugin->formatCollection($cid, $maxdocs, array($filterattr => $filterval));
			}
			$result .= '</ul>';
               ?>
              <?php echo $after_widget; ?>
        <?php
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {				
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['cid'] = strip_tags($new_instance['cid']);
		$instance['count'] = strip_tags($new_instance['count']);
		$instance['filterattr'] = strip_tags($new_instance['filterattr']);
		$instance['filterval'] = strip_tags($new_instance['filterval']);
        return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {				
        $title = esc_attr($instance['title']);
        $cid = esc_attr($instance['cid']);
        $count = esc_attr($instance['count']);
        $filterattr = esc_attr($instance['filterattr']);
        $filterval = esc_attr($instance['filterval']);
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
            <p><label for="<?php echo $this->get_field_id('cid'); ?>"><?php _e('Collection Id:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('cid'); ?>" name="<?php echo $this->get_field_name('cid'); ?>" type="text" value="<?php echo $cid; ?>" /></label></p>
 		<p><label for="<?php echo $this->get_field_id('count'); ?>"><?php _e('Number of docs to display:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>" type="text" value="<?php echo $count; ?>" /></label></p>
 		<p><label for="<?php echo $this->get_field_id('filterattr'); ?>"><?php _e('Attribute name to filter for:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('filterattr'); ?>" name="<?php echo $this->get_field_name('filterattr'); ?>" type="text" value="<?php echo $filterattr; ?>" /></label></p>
 		<p><label for="<?php echo $this->get_field_id('filterval'); ?>"><?php _e('Attribute value:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('filterval'); ?>" name="<?php echo $this->get_field_name('filterval'); ?>" type="text" value="<?php echo $filterval; ?>" /></label></p>
        <?php 
    }

} // class MendleyCollectionWidget

/**
 * MendeleySharedCollectionWidget Class
 */
class MendeleySharedCollectionWidget extends WP_Widget {
    /** constructor */
    function MendeleySharedCollectionWidget() {
        parent::WP_Widget(false, $name = 'Mendeley SharedCollection');	
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {		
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        $cid = apply_filters('widget_cid', $instance['cid']);
        $maxdocs = apply_filters('widget_cid', $instance['count']);
        $filterattr = apply_filters('widget_cid', $instance['filterattr']);
        $filterval = apply_filters('widget_cid', $instance['filterval']);
        ?>
              <?php echo $before_widget; ?>
                  <?php if ( $title )
                        echo $before_title . $title . $after_title; ?>
              <?php
              		$result = '<ul class="wpmlist">';
			if (strlen($filterattr)<1) {
				$result .= $mendeleyPlugin->formatSharedCollection($cid, $maxdocs);
			} else {
				$result .= $mendeleyPlugin->formatSharedCollection($cid, $maxdocs, array($filterattr => $filterval));
			}
			$result .= '</ul>';
               ?>
              <?php echo $after_widget; ?>
        <?php
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {				
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['cid'] = strip_tags($new_instance['cid']);
		$instance['count'] = strip_tags($new_instance['count']);
		$instance['filterattr'] = strip_tags($new_instance['filterattr']);
		$instance['filterval'] = strip_tags($new_instance['filterval']);
        return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {				
        $title = esc_attr($instance['title']);
        $cid = esc_attr($instance['cid']);
        $count = esc_attr($instance['count']);
        $filterattr = esc_attr($instance['filterattr']);
        $filterval = esc_attr($instance['filterval']);
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
            <p><label for="<?php echo $this->get_field_id('cid'); ?>"><?php _e('Shared Collection Id:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('cid'); ?>" name="<?php echo $this->get_field_name('cid'); ?>" type="text" value="<?php echo $cid; ?>" /></label></p>
 		<p><label for="<?php echo $this->get_field_id('count'); ?>"><?php _e('Number of docs to display:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>" type="text" value="<?php echo $count; ?>" /></label></p>
 		<p><label for="<?php echo $this->get_field_id('filterattr'); ?>"><?php _e('Attribute name to filter for:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('filterattr'); ?>" name="<?php echo $this->get_field_name('filterattr'); ?>" type="text" value="<?php echo $filterattr; ?>" /></label></p>
 		<p><label for="<?php echo $this->get_field_id('filterval'); ?>"><?php _e('Attribute value:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('filterval'); ?>" name="<?php echo $this->get_field_name('filterval'); ?>" type="text" value="<?php echo $filterval; ?>" /></label></p>
        <?php 
    }

} // class MendleySharedCollectionWidget

// register MendeleyWidget widget
add_action('widgets_init', create_function('', 'return register_widget("MendeleySharedCollectionWidget");'));


/***************************************************************************
 * Function: Run CURL
 * Description: Executes a CURL request
 * Parameters: url (string) - URL to make request to
 *             method (string) - HTTP transfer method
 *             headers - HTTP transfer headers
 *             postvals - post values
 **************************************************************************/
function run_curl($url, $method = 'GET', $headers = null, $postvals = null){
    $ch = curl_init($url);

    if ($method === 'GET'){
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    } else {
        $options = array(
            CURLOPT_HEADER => false,
            CURLINFO_HEADER_OUT => false,
            CURLOPT_VERBOSE => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $postvals,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => 3
        );
        curl_setopt_array($ch, $options);
    }
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

function redirect($url){
    if (!headers_sent()){    //If headers not sent yet... then do php redirect
        header('Location: '.$url); exit;
    }else{                    //If headers are sent... do java redirect... if java disabled, do html redirect.
        echo '<script type="text/javascript">';
        echo 'window.location.href="'.$url.'";';
        echo '</script>';
        echo '<noscript>';
        echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
        echo '</noscript>'; exit;
    }
}//==== End -- Redirect

function startsWith($string, $prefix, $caseSensitive = true) {
	if(!$caseSensitive) {
	return stripos($string, $prefix, 0) === 0;
	}
	return strpos($string, $prefix, 0) === 0;
}

function endsWith($string, $postfix, $caseSensitive = true) {
	$expectedPostition = strlen($string) - strlen($postfix);
	if(!$caseSensitive) {
		return strripos($string, $postfix, 0) === $expectedPostition;
	}
	return strrpos($string, $postfix, 0) === $expectedPostition;
}


?>
