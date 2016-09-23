[phpFlickr](https://github.com/dan-coulter/phpflickr)
=====================================================
by [Dan Coulter](http://twitter.com/danco)

A PHP wrapper for the Flickr API.

Installation
============

1.  Copy the files from the installation package into a folder on your
    server.  They need to be readable by your web server.  You can put 
    them into an include folder defined in your php.ini file, if you 
    like, though it's not required. 
    
2.  All you have to do now is include the file in your PHP scripts and 
    create an instance.  For example:
    $f = new phpFlickr();

    The constructor has three arguments:
    1.  $api_key - This is the API key given to you by flickr.com. This 
        argument is required and you can get an API Key at:
        https://www.flickr.com/services/api/keys/
        
    2.  $secret - The "secret" is optional because is not required to 
        make unauthenticated calls, but is absolutely required for the 
        new authentication API (see Authentication section below).  You 
        will get one assigned alongside your api key.
    
    3.  $die_on_error - This takes a boolean value and determines 
        whether the class will die (aka cease operation) if the API 
        returns an error statement.  It defaults to false.  Every method 
        will return false if the API returns an error.  You can access 
        error messages using the getErrorCode() and getErrorMsg() 
        methods.
        
3.  All of the API methods have been implemented in my class.  You can 
    see a full list and documentation here: 
        http://www.flickr.com/services/api/
    To call a method, remove the "flickr." part of the name and replace 
    any periods with underscores. For example, instead of 
    flickr.photos.search, you would call $f->photos_search() or instead 
    of flickr.photos.licenses.getInfo, you would call 
    $f->photos_licenses_getInfo() (yes, it is case sensitive).
    
    All functions have their arguments implemented in the list order on 
    their documentation page (a link to which is included with each 
    method in the phpFlickr clasS). The only exceptions to this are 
    photos_search(), photos_getWithoutGeodata() and 
    photos_getWithoutGeodata() which have so many optional arguments
    that it's easier for everyone if you just have to pass an 
    associative array of arguments.  See the comment in the 
    photos_search() definition in phpFlickr.php for more information.
    

Authentication
==============
As of this release of the phpFlickr class there is only one authentication method
available to the API.  This method is somewhat complex, but is far more secure and
allows your users to feel a little safer authenticating to your application.  You'll
no longer have to ask for their username and password.

[Flickr Authentication API](http://www.flickr.com/services/api/auth.spec.html)
    
I know how complicated this API looks at first glance, so I've tried to
make this as transparent to the coding process.  I'll go through the steps
you'll need to use this.  Both the auth.php and getToken.php file will
need your API Key and Secret entered before you can use them.
    
To have end users authenticate their accounts:
1.  setup a callback script.  I've included a callback script that 
    is pretty flexible.  You'll find it in the package entitled "auth.php".

    You'll need to go to flickr and point your api key to this file as the 
    callback script.  Once you've done this, on any page that you want to 
    require the end user end user to authenticate their flickr account to 
    your app, just call the phpFlickr::auth() function with whatever 
    permission you need to use.

    For example:
        $f->auth("write");

    The three permissions are "read", "write" and "delete".  The function
    defaults to "read", if you leave it blank.  
        
    Calling this function will send the user's browser to Flickr's page to 
    authenticate to your app.  Once they have logged in, it will bounce
    them back to your callback script which will redirect back to the
    original page that you called the auth() function from after setting
    a session variable to save their authentication token.  If that session
    variable exists, calling the auth() function will return the permissions
    that the user granted your app on the Flickr page instead of redirecting
    to an external page.
    
2.  To authenticate the app to your account to show your private pictures (for example)
        
    This method will allow you to have the app authenticate to one specific
    account, no matter who views your website.  This is useful to display
    private photos or photosets (among other things).
    
    *Note*: The method below is a little hard to understand, so I've setup a tool
    to help you through this: http://www.phpflickr.com/tools/auth/.
                    
    First, you'll have to setup a callback script with Flickr.  Once you've
    done that, edit line 12 of the included getToken.php file to reflect 
    which permissions you'll need for the app.  Then browse to the page.
    Once you've authorized the app with Flickr, it'll send you back to that
    page which will give you a token which will look something like this:
        1234-567890abcdef1234
    Go to the file where you are creating an instance of phpFlickr (I suggest
    an include file) and after you've created it set the token to use:
        $f->setToken("<token string>");
    This token never expires, so you don't have to worry about having to
    login periodically.
        

Caching
=======

Caching can be very important to a project.  Just a few calls to the Flickr API
can take long enough to bore your average web user (depending on the calls you
are making).  I've built in caching that will access either a database or files
in your filesystem.  To enable caching, use the phpFlickr::enableCache() function.
This function requires at least two arguments. The first will be the type of
cache you're using (either "db" or "fs")
    
1.  If you're using database caching, you'll need to supply a PEAR::DB style connection
    string. For example: 

        $flickr->enableCache("db", "mysql://user:password@server/database");
        
    The third (optional) argument is expiration of the cache in seconds (defaults 
    to 600).  The fourth (optional) argument is the table where you want to store
    the cache.  This defaults to flickr_cache and will attempt to create the table
    if it does not already exist.
    
2.  If you're using filesystem caching, you'll need to supply a folder where the
    web server has write access. For example: 
    
        $flickr->enableCache("fs", "/var/www/phpFlickrCache");
    
    The third (optional) argument is, the same as in the Database caching, an
    expiration in seconds for the cache.

    Note: filesystem caching will probably be slower than database caching. I
    haven't done any tests of this, but if you get large amounts of calls, the
    process of cleaning out old calls may get hard on your server.
        
    You may not want to allow the world to view the files that are created during
    caching.  If you want to hide this information, either make sure that your
    permissions are set correctly, or disable the webserver from displaying 
    *.cache files.  In Apache, you can specify this in the configuration files
    or in a .htaccess file with the following directives:
        
        <FilesMatch "\.cache$">
            Deny from all
        </FilesMatch>
    
    Alternatively, you can specify a directory that is outside of the web server's
    document root.
        
Uploading
=========

Uploading is pretty simple. Aside from being authenticated (see Authentication 
section) the very minimum that you'll have to pass is a path to an image file on 
your php server. You can do either synchronous or asynchronous uploading as follows:

    synchronous:    sync_upload("photo.jpg");
    asynchronous:   async_upload("photo.jpg");
    
The basic difference is that synchronous uploading waits around until Flickr
processes the photo and returns a PhotoID.  Asynchronous just uploads the
picture and gets a "ticketid" that you can use to check on the status of your 
upload. Asynchronous is much faster, though the photoid won't be instantly
available for you. You can read more about asynchronous uploading here:

    http://www.flickr.com/services/api/upload.async.html
        
Both of the functions take the same arguments which are:

> Photo: The path of the file to upload.  
> Title: The title of the photo.  
> Description: A description of the photo. May contain some limited HTML.  
> Tags: A space-separated list of tags to apply to the photo.  
> is_public: Set to 0 for no, 1 for yes.  
> is_friend: Set to 0 for no, 1 for yes.  
> is_family: Set to 0 for no, 1 for yes.
        
Replacing Photos
================

Flickr has released API support for uploading a replacement photo.  To use this
new method, just use the "replace" function in phpFlickr.  You'll be required
to pass the file name and Flickr's photo ID.  You need to authenticate your script
with "write" permissions before you can replace a photo.  The arguments are:

> Photo: The path of the file to upload.  
> Photo ID: The numeric Flickr ID of the photo you want to replace.  
> Async (optional): Set to 0 for a synchronous call, 1 for asynchronous.  
    
If you use the asynchronous call, it will return a ticketid instead
of photoid.

Other Notes:
1.  Many of the methods have optional arguments.  For these, I have implemented 
    them in the same order that the Flickr API documentation lists them. PHP
    allows for optional arguments in function calls, but if you want to use the
    third optional argument, you have to fill in the others to the left first.
    You can use the "NULL" value (without quotes) in the place of an actual
    argument.  For example:
    
        $f->groups_pools_getPhotos($group_id, NULL, NULL, 10);

    This will get the first ten photos from a specific group's pool.  If you look
    at the documentation, you will see that there is another argument, "page". I've
    left it off because it appears after "per_page".

2.  Some people will need to ues phpFlickr from behind a proxy server.  I've
    implemented a method that will allow you to use an HTTP proxy for all of your
    traffic.  Let's say that you have a proxy server on your local server running
    at port 8181.  This is the code you would use:

        $f = new phpFlickr("[api key]");
        $f->setProxy("localhost", "8181");

    After that, all of your calls will be automatically made through your proxy server.
 
