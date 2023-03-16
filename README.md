# opcms v3.0
 
OppidumCMS 3 - Special API Version

The 3rd version of OppidumCMS is mainly focused on API functionalities, and can serve as a backend for both website and webapp frontend.

OppidumCMS is developed by the one-man-team Lucius Arkay.



**OppidumCMS features:**
- Ultra-lightweight (including few dependencies)
- Single-table
- MVC architecture
- Frontend engine with built-in URL rewriting
- Multilingual ready
- Works as a Web-Service (can be implemented in separate apps, like an API)
- Polymorphic, generic objects: deploy custom modules within minutes
- Both frontend and backend highly customizable (OppidumCMS is a canvas of a CMS)

**Updates in version 3.0 compared to version 2.x**
- As of 3.0, all object types can be accessed through an API endpoint. But only the object types which are activated (= .json file being present) in the modules folder can be accessible. By default this access is public, if you need fully private modules you can configure so in the .json description of the module (check "dummy.json" for an example).
- As of 3.0, OpCMS doesn't require a 'site' and a 'user' entry in the database. Site ParamsThe admin panel can work with zero content in database.
- As of 3.0, an object type can no longer be modified in admin.
- In a purpose of simplification, the starter theme has been removed, and only the "pico" theme, which doesn't use any javascript, is used in the admin. The only exception being the admin/uploads page. If you need anything more dynamic, you can create a webapp in a modern JS framework and use the API to create your own custom admin pages.
- "User" blob type was removed, as well as the "author" field and everything related to access level, since these functionalities were never used. OpCMS v3 focuses on simple API CRUD requests, and it relies now on minimal settings, which means a single superuser which can be configured in config.php. Multi user, or most probably API keys, might be added in the future.
- In PageController.php you can now easily switch between a support for frontend (= PHP generates standard web pages) or JSON responses (= PHP serves data for web apps only)

## Requirements
- A web server, preferably a Linux - Apache - MariaDB - PHP stack.
- Software packages: **git**, **composer**.
- Dependencies installed by composer: slim PHP core, Twig templating engine with some extensions, and PHPMailer.
- Dependencies installed in code: Bootstrap, jQuery, Parsedown, and other modules in /external.

## Installation
- `git clone https://bitbucket.org/cbwebdev/opcms2.git`
- `composer install` in the public folder, to generate the public/vendor folder.
- If using docker: run `docker-compose up`
- copy config.sample.php to config.php
- copy/paste the SQL table structure from config.php to PHPMyAdmin or Adminer
- setup your own configuration in config.php
- if you need to use the uploading tool available in the admin, create an 'uploads' folder in /public and allow write permissions to your webserver.
- Done ! You set up your Oppidum CMS site.
- (Optionally: check the rendering mode in PageController, and activate JSON or frontend depending on your needs)


## Single-table
- OppidumCMS uses a single table and polymorphic objects (called "blobs") which uses the same fields, be it a page, a paragraph, a media, a user, a custom object, etc. In OppidumCMS the term BLOB stands for Blobby Long Object, in other words, a polymorphic object where any of its types uses the same basic fields ("params" being the array of additional fields).

	- INT(11) id: unique identifier
	- VARCHAR(32) type: object type (i.e. "page", "paragraph", "image", "littlepony" etc.)
	- VARCHAR(128) url: unique resource locator
	- VARCHAR name: human-readable name for the object
	- TEXT content: directly loaded content
	- INT parent: id of parent object
	- INT status: 1=online, 0=offline, -1=deleted
	- TIMESTAMP edited: date of last modification
	- VARCHAR lang: language identifier of the resource
	- INT translation_of: id of an object which the object is a translation of
	- TEXT params: all the custom parameters
	
## config.php

### array $config
- bool displayErrorDetails: ?
- bool addContentLengthHeader: ?
- array db: database info
	- string host: database host (e.g. "localhost")
	- string user: database user (e.g. "root")
	- string pass: database password
	- string dbname: database name
	- string tbl: opcmsdev
- array site: site parameters (formerly the "site" entry in OpCMS 2)
	- string name: Default site title (if frontend is active)
	- string content: Default site meta description (if frontend is active)
	- array params:
		- string default_language: the default language code
		- string homelink: the url of the home page (must be in the same language as the default lang.)
		- array languages: contains an array of the active languages ['code':'Language name']
- array user: the default user (formerly the first "user" entry in OpCMS 2)
	- string login: The login or email to access the admin panel or to query objects with status = 0
	- string password: The password to access the admin panel or to query objects with status = 0
- string abspath: the site's URL with http(s)://
- string absdir: the site's root path on the server (e.g. /var/www/opcms)
- string template_dir: relative path to the activated template




## index.php

### MVC (model-view-controller)
OppidumCMS uses a model-view-controller architecture based on Slim PHP's routing system.  The process is:

- The called URL will be executed by the controller which handles its route (i.e. AdminController handles all /admin pages, BlobController handles blob creation/update/etc., PageController handles the routes of the public pages, etc.)
- The controller executes some code, retrieves some parameters, and pass those parameters either to the view renderer, or to the JSON renderer.
- The view renderer passes all the aforementioned parameters either to JSON, or to the root template **standard.html.twig** if the frontend is used, and executes it. The *action* parameter defines which page / partial template will be loaded.

If you create custom models and controllers, please load them at the bottom of index.php.

## Controllers
OppidumCMS is an optimized API motor with layers of frontend and backend views upon it.
Controllers are located in /public/controllers

### AdminController
Renders admin backend pages. All functions require SESSION.

- admin /admin: redirects to userLogin or adminDashboard. 
- adminCreate /admin/create: renders edit from with the action of object creation.
- adminDashboard /admin/dashboard/{page}: renders adminDashboard list with pagination.
- adminDashboardByType /admin/dashboard/type/{type}/{page}: renders adminDashboard list from a single object type with pagination.
- adminEdit /admin/{id}/edit: renders edit form with the action of object edition.
- adminRecycle: /admin/recycle: renders admin recycle bin list.
- adminSitemap: /admin/sitemap: renders admin site map view. Allows an optional GET parameter langFilter to filter by language.
- adminUploads: /admin/uploads/{subdir}: renders the uploads manager.


### BlobController
Before V3 it handled all /blob routes, but they have been replaced by /{type} routes, where {type} is the current blob's type.

- createBlob /{type}/create (POST): Creates a new blob from a formData, and returns JSON status or redirects to a callback URL if provided. Requires:
	- param $callback: the callback URL
	- string $name
	- url $url
	- string $content
	- int $parent
	- int $status (-1,0,1)
	- param $lang
	- int $translation_of
	- json $params
	- SESSION
- deleteBlob /{type}/{id}/delete: Deletes blob {id} if status = -1, or else sets blob status to -1. Returns JSON status. Will fail if the given {type} doesn't match with {id}'s type. Require SESSION.
- getBlob /{type}/{id}: Displays blob {id} in JSON format, if its {type} allows public display and if its status is 1 (Requires SESSION in order to display a blob with status = 0)
- updateBlob /{type}/{id}/update (POST): Updates an existing blob from a formData, and returns JSON status or redirects to a callback URL if provided. Will fail if the given {type} doesn't match with {id}'s type. Requires:
	- int $id
	- param $callback: the callback URL
	- string $name
	- url $url
	- string $content
	- int $parent
	- int $status (-1,0,1)
	- param $lang
	- int $translation_of
	- json $params
	- SESSION


===== WORK IN PROGRESS - WHAT COMES AFTER THIS LINE IS STILL V2 AND HASN'T YET BEEN UPDATED FOR V3 =====
	

### FormController
- formSend /form/send (POST): Sends form. Requires:
	- array $formData: a whole form data that will be transmitted to FormModel->sendForm. See Form Model for doc.

### MediaController
- mediaFileUpload /media/fileUpload (POST): Retrieves form data with uploaded files, moves them in the /uploads structure, and returns a JSON status. Requires SESSION.
- mediaDelete /media/delete/{src}: executes deleteMedia() to delete a file. Returns JSON status. Requires SESSION. 
- mkdirMedia /media/mkdir (POST): creates a folder. Returns redirection to callback. Requires:
    - SESSION
    - mkdir-name: the folder name
    - mkdir-parent: the callback folder in /uploads
- rmdirMedia /media/rmdir/{src}: removes an empty folder. Returns redirection to callback or JSON 500 error. Requires:
    - SESSION
    - src (via url): the folder name
    - callback (via GET): the callback folder in /uploads

### PageController
- homePage / : Renders website's public home page.
- page-{id} /{url} : PageController contains the url rewriting system that generate a route for each public page. Oppidum CMS allows a maxium of 3 page sub-levels.

### UserController
- userLogin /user/login: renders the login form
- userPostLogin /user/login (POST): retrieves login form data, tries to login, and returns JSON status or redirects to a callback URL if provided. Requires:
	- param $callback: the callback URL if login succeeded. If unset, returns the success message as a JSON status.
	- bool $callbackIfError: if true, renders login form with the error messsage; if false, returns the error message as a JSON status.
	- array $userAdmin:
		- param $login: existing user login
		- string $password: existing user password
- userLogout /user/logout: destroys SESSION and returns JSON status. Callback to ABSPATH if GET.
- userSignup /user/signup: renders the signup form
- userPostSignup /user/signup (POST): retrieves signup form data, creates user and renders the signupValidate action, or re-renders signup form with errors if any. Requires:
	- array $userAdmin:
		- param $login: new user login
		- param $email: new user email
		- string $password: new user password
		- string $password_confirm: new user password confirmation (identical test)
	- SITE.allowUserSignup: user signup must be enabled in SITE.params in database.

## Classes (Models)
Classes contain methods called inside controllers. They are located in /public/classes.
In order to use a class Xyz, it must be constructed as:
<?php $xyzModel = new Xyz($container->db);?> with the optional database parameter.

### Admin model
Admin / backend operations.

- canEdit: check if SESSION user is allowed to edit the blob $id. Requires:
	1. int $id: id of the blob

- canDelete: check if SESSION user is allowed to delete the blob $id. Requires:
	1. int $id: id of the blob
	
- getPageContent (PRIVATE): selects all elements, except pages, whose parent are given id. Used in sitemap generation. Requires:
	1. int $parentID: id of the page (or parent blob)
	2. array $args:
		 - bool $admin: add to fetched data an "admin" fields containing the "canEdit" and "canDelete" statuses for elements.
		 
- getSitemap: generates the root of the sitemap and begins the getSitemapTree loop. Requires:
	1. array $args:
		- bool $admin: add to fetched data an "admin" fields containing the "canEdit" and "canDelete" statuses for elements.
		
- getSitemapTree (PRIVATE) (Tree): executes one loop of the tree generation of the sitemap.  Returns a fetched array with "elements" (getPageContent) and children (getSitemapTree's next loop). Requires:
	1. array $siteRoot: the SITE's blob generated by Blob->getSiteProperties()
	2. array $args:
		- bool $admin: add to fetched data an "admin" fields containing the "canEdit" and "canDelete" statuses for elements.
		- param $langFilter: allows a WHERE clause to filter by lang.

### API Model
API operations

- getAllRoutes: returns an array of all existing routes in the site. Used for debug purposes, should only be used as admin.

### Blob Model
Operations related to any object, used mostly by the admin CRUD editor.

- addBlob: adds a new blob of any type. Requires:
	1. array $post: data corresponding to database fields.

- deleteBlob: deletes a blob. Requires:
	1. int $id: the ID of the blob

- getAllBlobs: get a filtered list of blobs, by type, by status, with or without pagination, etc. Requires:
    1. array $args:
    - mixed $type: get blobs from this specific type, or from this specific array of types.
	- int $paged: number of elements by page
	- int $page: get the data of a specific page
	- array of int $status: filter by blobs having the given statuses
	- param $orderby: order by specific field
	- param $order: ASC or DESC
	- int $parent: get blobs having this specific parent
	- param $searchTerm: get blobs having this term in ID or name or content or params. Used in admin dashboard.
	- bool $onlyCount: only get the amount of blobs selected, not the content. Used for pagination, for example.
	- int $limit: limit the result to X blobs.
	

- getAuthor: get a blob's author ID. Requires:
    1. int $id: the blob's ID.

- getBlob: get a specific blob by ID. Requires:
    1. int $id: the blob's ID.
    2. array $args:
	- bool $rawParams: if false (by default): decode JSON params as an array. If true, get the raw JSON data instead.
	
- getBlobStatus: get a blob's status. Requires:
     1. int $id: the blob's ID.

- getBlobType: get a blob's type. Requires:
     1. int $id: the blob's ID.

- getDefaultParams: get the list of default params for a specific blob's type. This data is stored in /modules/{{type}}.json. Requires:
     1. param $type: the requested type.

- getSiteProperties: get the blob of type "site", which should be unique. This blob, generally with database ID = #1,  contains the site parameters. Requires no params.
    
- isTheAuthor: check if connected user is the author of a blob. Requires:
    1. int $id: the blob's ID.
    
- setBlobStatus: changes the status of a blob. Requires:
    1. int $id: the blob's ID.
    2. int $status. the blob's new status.

- updateBlob: updates a blob's entry. All fields are rewritten, so it requires everything. Params must be given as an array, and are encoded as JSON in this function. Requires:
    1. int $id: the blob's ID.
    2. array $post: data corresponding to database fields.

### Block model
Block operations

- getBlocks: get all the blocks of the website with their data. Usually it is used to send the block data to the page view and simply display all blocks on the template.
- getBlockElements: used in getBlocks to get a block's data.
- getBlockExternalData: a block can have an additional dataset from the website for various reasons, like dynamic content. This function retrieves the external data from the url: {{ABSPATH}}/ {{block.params.externalData}}.

### Form model
Form operations

- sendForm: sends the form with PHPMailer and returns a status and statusText. Requires:
	1. that the form has a "mailto" parameter 
	2. that the config(.php) has a "phpmailer" "host", "user", and "pass".
	3. array $post: a whole post data, containing at least:
		- array $formData
			- int $id: the form ID transmitted via an input type="hidden". Is used for the callback, sendForm() retrieves the parent page and redirects to it.
			- string $name: Sender's name, will be used in the SetFrom
			- email $email: Sender's email, will be used for the ReplyTo.

### Media model
Media operations

- generateThumbnail: creates, from a given absdir of image, a thumbnail image with uploaded image's name plus "-WxH". Returns the thumbnail's image. No error handling, assuming that the uploads dir is writeable. Requires:
	1. string $img: image path in the absdir (absolute directory) format.
	2. int $twidth: thumbnail width in pixels, default 300
	3. int $theight: thumbnail height in pixels, default 300
	4. int $quality: for jpeg images, thumbnail quality (0-100), default 90

- moveMedia: moves a media to a subfolder or to the parent folder. Requires:
	1. current path of the file relative to /uploads $from
	2. destination path of the file relative to /uploads $to

- moveUploadedFile: moves a file, which was just received through a POSTed form, into the uploads structure. Requires:
	1. string $directory: directory where the file must be put in, in the absdir format. 
	2. object $uploadedFile: a SlimPHP object containing a single file from the file list generated by Request.getUploadedFiles() in the controller.

- deleteMedia: deletes file $src with unlink(). Requires:
	1. string $src: relative path of the file to delete, which comes after ABSDIR/uploads/{...}
	
### Page model
Page operations

- isHome: check if page $id is homepage, returns true or false. Requires:
	1. int $id: a page's id.
	
- getHomePage: get the homepage ID, with or without its content. Requires:
	1. bool $withContent: returns fetched row if true, or only homepageID if false.
	
- getPageElements: get page elements for sitemap or frontend display. Returns a fetched array with added "listing" array if the blob is a reference list, and sorted by the blob.params.order parameter which defines the order of elements on the page. Requires:
	1. int $id: a page's id.
	2. string $limit: the X,Y part of the LIMIT sql query (i.e. "0,10")
	3. string $type: only fetches page elements of this type (i.e. "paragraph"). Default Null. If Null, fetches anything except sub"page"s. 
	
- getTranslations: returns the blobs which have a translation_of field equal to the given blob id. Used in language menus. Requires:
	1. int $tr: the page's id equal to the translation group id.

### User model
User login and signup functions	

### Validator model
Input validation operations

- asDateTime: validates string as date/time with SQL format.  Requires:
	1. string $s: a date or timestamp
	
- showDate, showDateTime, showHumanDateTime: outputs a date. This will probably be removed and a twig extension be used instead. Require:
	1. string $d: a date
	2. string $lang: a language for the format (only 'fr' is implemented yet).

- showImage: displays an image from an image blob. Requires:
	1. blob $blob: an image blob having the existing params:
		- string $blob.params.src: source to the actual file
		- string $blob.params.link: the <a> link that will wrap the <img />
		- string $blob.params.link_target: the link target (i.e. _blank)
		- bool $blob.params.link_self: if true, the <a> link will open the image itself in browser.
		- string $class: the class parameter of the <img />

- showResponsiveImage: triggers generateThumbnail() and displays an image from an image blob. Can be further developed into a srcset responsive system. Requires:
	1. blob $blob: an image blob having the existing params:
		- string $blob.params.src: source to the actual file
		- string $blob.params.link: the <a> link that will wrap the <img />
		- string $blob.params.link_target: the link target (i.e. _blank)
		- bool $blob.params.link_self: if true, the <a> link will open the image itself in browser.
		- string $class: the class parameter of the <img />
	2. array $args:
		- int $width: thumbnail width, default 150
		- int $height: thumbnail height, default 150,
		- int $quality: if jpeg, thumbnail quality (0-100), default 80
		
- showThumbnail: (Might be obsolete, uses the "media" object)

- asEmail: validates $s as email with a regexp. Requires:
	1. string $s: a string that should be a valid email.
	
- asJson: returns json_decode if $s is valid JSON, false if invalid. Avoid saving invalid params in database. Requires:
	1. string $s: a string that should be valid JSON.

- asParam: applies trim, strip_tags, htmlspecialchars, htmlentities, and tricks on quotes, on string $s. Requires:
	1. string $s: a string that should be used as a site parameter.

- asString: applies trim and htmlentities on string $s. Requires:
	1. string $s: any text.
	
- asStatus: check if number $n is either -1, 0, or 1. Returns $n if valid, false if not. Requires:
	1. int $n: a number that should be either -1, 0, or 1.

- asURL: check if string $s is 1) not empty, 2) not existent in the getAllRoutes() list, 3) doesn't contain tags, specialchars, and quotes, and 4) equal to itself filtered with rawurlencode(). Returns false if any of these tests fail, returns $s if all succeed. Requires:
	1. string $s: a string that should be used as a blob url.
	
- toFileName: for basic file handling with PHP: replaces all spaces with underscores and removes all accents.
	1. string $string: any text made of latin characters.
	
- replaceAccents: replaces accented latin characters with non-accented counterparts. Used by Validator::toFileName.
	1. string $s: any text made of latin characters.

- parseForm: used for custom forms: replaces (with a regex) the following tags to their HTML counterparts. For example in your form twig template, you can show the fields with {{ validate.parseForm(element.content) }} if element.content is not empty, and display default fields otherwise. This function doesn't render the submit button, default hidden fields, nor any other form tags, those should still be added manually.
{input name "Label"} => input type text
{email name "Label"} => input type email
{text(area) name "Label"} => textarea
1. string $html = the pseudo code

- parseMd: uses Parsedown and a few custom methods to transform Markdown text into html. Requires:
	1. string $html: Markdown text that will be parsed to HTML on-the-fly.
	2. bool $paragraphs: Parsedown generates paragraphs if true, lines if false. Default true.

- removeMd: uses Parsedown to convert to tags and then strip tags, thus removing the markdown and converting to plain text. Useful, for example, if you want to show blog excerpts without the markdown contained in the blog articles.	

- asInt: validate as an integer number. Executes (int)$s on $s, returns the value if succeeded, false if failed. Requires:
	1. string $s: user-input text that should be an integer.

- asUnsignedInt: validate as a non-negative integer number. executes (int)$s and abs(). Returns the value if succeeded, false if failed. Requires:
	1. string $s: user-input text that should be a non-negative integer.
	
- asPriceFormat: formats a number with 2 decimals with number_format() and returns it as a string. Requires:
	1. int $price: any number.
	2. string $lang: not used, but may be used for different language formatting.
This might be removed and replaced with a Twig extension.

- validateArray (Tree): executes a filter on all elements of an array. Requires:
	1. array $array: any array of data of any depth.
	2. functionName $asTextType: an existing function of Validator to be executed on all elements (e.g. "asString", "asParam").
	
- showBlobLink: generates a link to an internal element.
	1.  int $id : the element's ID.
	2.  string $linkText: the text of the link
	3.  param $params.target: optional target parameter i.e. "_blank"

- showExcerpt: show the first X characters of a text, with an ellipsis (...)
	1. string $content: the text
	2. int $limit: the number of characters. By default: 50

## Modules

Modules have default params which are used in create and edit forms, they are indicated in /modules/{module].json.

The list of parameters is documented in each one of the  /modules/{module].json files.

Here are some of the default OppidumCMS modules (this list might be outdated):

- **site:** Site blob is the website's root element, containing site-wide configuration. There should be max. 1 site blob per site.
	- string homelink: the url field of the home page (must be set and changed manually)
	- object languages: the active languages on the website in an object format, where the key is the 2-letter short language identifier, and the value is the human-readable name of the language. Example {"en":"English","fr":"Français"}
	- string default_language: the 2-letter short language identifier for the default language (i.e. "en", "fr", "de", etc.)

- **user:** User blobs handle login info and access rights for the website's users. A fresh install comes with one user called *admin*.
	- int level: the access rights level: "3" is (super)admin privileges, "2" is moderator, "1" is contributor, and "0" is simple user with no editing rights. "-1" (-2,-3...) could be used to handle banned users with different banning reasons.
	- the status field is used for validating or deleting users: "1" is active, "0" is pending (confirm e-mail etc.), and "-1" is for a user who deleted his own account.
	
TODO:  most of the user access rights and stuff are not yet developed.

- **page:** Page blobs are used for displaying pages, and their URL field is automatically used for URL rewriting. OppidumCMS can handle a maximum sub-page depth of 3 levels.
	- unsigned_int order: the numeric order in which pages come in the sitemap.
	- string metaDescription: the <meta name="description" /> tag for search engines.
	- the parent field should be the site blob's ID if a 1st level page, or another page's ID if a subpage.
	- the content field of page blobs should remain empty.

- **paragraph:** Paragraph blobs are simple blocks of text, they are generally children of page blobs.
	- unsigned_int order: the numeric order in which the page content is shown.
	- bool hideTitle: if "0" or false, the paragraph's title won't be displayed.
	
- **image:** Image blobs contain a reference to a file in the /uploads folder, and other parameters. They are generally children of page blobs.
	- unsigned_int order: the numeric order in which the page content is shown.
	- string src: the part of the access path which follows "/uploads". Example: the parameter must be "/img.jpg" for an image located at {ABSDIR}/uploads/img.jpg, or "/subfolder/img2.jpg" for an image located at {ABSDIR}/uploads/img2.jpg.
	- string class: the class="" parameter for the <img /> tag. Include all the classes as you would do in HTML, example: "figure-img img-fluid rounded".
	- bool caption: displays (or not) the image in a <figure> tag with a <figcaption> containing both the title and the description (name and content fields).
	
- **block** A block displayed on several pages, for example, in a banner. Can contain paragraphs and other content.

- **csv** A CSV parser. Only uses a controller with 2 routes

- **form:** A simple contact form
	- int order: order on the page
	- email mailto: the destination address
	- string subject: message title (not used, probably for multilingual?)
	- number nonce: an optional custom number to strengthen the antispam field.
	- object delivery: contain the success and error messages (^not used, probably for multilingual?)
	
- **gallery** An image gallery
	- int order: order on the page
	- string format: the CSS classes on the elements (i.e. "col-md-3", "col-md-4 col-sm-6"...)
	- string folderBase: the folder name (i.e. "galleries/gal1" for /uploads/galleries/gal1)
	
- **html:** Custom HTML block
	- unsigned_int order: the numeric order in which the page content is shown.
	- bool hideTitle: if "0" or false, the paragraph's title won't be displayed.
	
- **map:** A map with a single marker and popup, using leaflet.js
	- int order: order on the page
	- float lat: latitude
	- float lng: longitude
	- int zoom: default zoom (1-20)
	- string popupText: content of the popup text
	- bool popupOpened: whether popup is opened or closed on page load
	
	

## Pseudo-blobs ##

- **_opcms_error** This "bogus" blob type is not present in the database, it's only shown in Sitemap as an error handler to display an invalid blob. For example: if there is twice the same value for order in some page's content. To be documented.

	
## Base template
OppidumCMS's base template is a minimalist display of all the parameters, and is mostly suitable for development or debugging purposes. You should consider creating your own new template.

### standard.html.twig ###
This file is the base template, from <html> to </html>, called on every page. The dynamic content is provided by the various Controllers who give these parameters to the Twig template engine. The main parameters are:
- action: a string that requires which partial templates will be loaded. It is used for admin and user login pages. If no action is provided, then the basic frontend template will likely be loaded.
- object session: contains the $_SESSION data.
- object site: contains the site blob's data.
- object blob: if a single element is shown (i.e. a page, an article, etc.) then the 
- object elements: if several elements are shown
- string status: if a status message is shown, contains the status class (i.e. "success", "warning", "error", etc.)
- string statusText: if a status message is shown, contains the details.
- object sitemap: contains the whole site hierarchy, can be used to show the main navigation menu.
- bool isHome: true if is home page

### headlinks.html.twig and bodylinks.html.twig ###
These two files contain the link, meta, or script tags that must be present either in header or footer. Put your CSS and JS here.

### Admin views of base template ###
All the admin-{...}.html.twig and user-{...}.html.twig are called by standard.html.twig, instead of the frontend template, depending on the action. 

### Module views ###
OppidumCMS uses templates named after the modules that are "pageElement"s , like paragraph.html.twig for paragraphs,  image.html.twig for image blobs, etc.
The standard.html.twig calls the different module templates when necessary, but if you create new modules you must add manually your new blob type in the page content's inclusion list. The default template plainly displays the blob.name and the blob.content for unknown blob types or blob types not added in the inclusion list.

## Admin template
OpCMS can use a separate admin theme, or the same theme as frontend. By default no admin theme is provided and admin pages are on the same theme as the frontend, but activating $container->viewAdmin instead of $container->view can activate the admin theme renderer.

## Others
### /public/external and /public/css folders:
Contains external libraries used globally, independently from the theme. This should be cleaned and put in the frontend theme or admin theme.
