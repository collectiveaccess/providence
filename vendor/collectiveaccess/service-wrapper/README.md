A simple PHP wrapper for the new JSON-based REST web service API of CollectiveAccess

Please visit http://www.collectiveaccess.org for more information and refer to
http://docs.collectiveaccess.org for detailed information on the service API and
other features of the core software.

You can install this library via composer and then take advantage of composers
flexible autoloading feature. From there you can just use all the service classes
in the CollectiveAccessService namespace.

For example:

```php
$client = new CollectiveAccessService\ItemService("http://localhost/","ca_objects","GET",1);
$result = $client->request();
print_r($result->getRawData());
```

This should get you a generic summary for the object record with object_id 1.

Here are some more simple examples for the other service endpoints to get you started:

```php
$vo_client = new CollectiveAccessService\ModelService("http://localhost/","ca_entities");
$vo_client->setRequestBody(array("types" => array("corporate_body")));
$vo_result = $vo_client->request();

$vo_result->isOk() ? print_r($vo_result->getRawData()) : print_r($vo_result->getErrors());
```

```php
$vo_client = new CollectiveAccessService\SearchService("http://localhost/","ca_objects","*");
$vo_client->setRequestBody(array(
	"bundles" => array(
		"ca_objects.access" => array("convertCodesToDisplayText" => true),
		"ca_objects.status" => array("convertCodesToDisplayText" => true),
		"ca_entities.preferred_labels.displayname" => array("returnAsArray" => true)
	)
));
$vo_result = $vo_client->request();

$vo_result->isOk() ? print_r($vo_result->getRawData()) : print_r($vo_result->getErrors());
```

To use authentication, you basically have 3 options. The first is to use the PHP constants
`__CA_SERVICE_API_USER__` and `__CA_SERVICE_API_KEY__` as shown in the next example,
This comes in handy if you want to run multiple service requests in the same script.

Note that all 3 authentication options try to retrieve an authToken from the remote service,
save it in a temporary directory and re-use it as long as it's valid. When it expires, it
re-authenticates using the username and key provided using one of the 3 options below. user/key
are not used in the mean time.

Now back to option one - the constants:

```php
define('__CA_SERVICE_API_USER__', 'administrator');
define('__CA_SERVICE_API_KEY__', 'dublincore');

$o_service = new CollectiveAccessService\ItemService('http://localhost', 'ca_objects', 'GET', 1);
$o_result = $o_service->request();
```

You can also use a simple setter:

```php
$o_service = new CollectiveAccessService\ItemService('http://localhost', 'ca_objects', 'GET', 1);
$o_service->setCredentials('administrator', 'dublincore');
$o_result = $o_service->request();
```

The 3rd option (and probably most suitable for production) is to pass the credentials as environment variables
`CA_SERVICE_API_USER` and `CA_SERVICE_API_KEY`. Imagine this simple script as `authtest.php`

```php
$o_service = new CollectiveAccessService\ItemService('http://localhost', 'ca_objects', 'GET', 1);
$o_result = $o_service->request();
```

Then running something like this in a terminal should work:

```bash
export CA_SERVICE_API_USER=administrator
export CA_SERVICE_API_KEY=dublincore
php authtest.php
```

To do this in a web server setting, you could look into [apache's mod_env](http://httpd.apache.org/docs/2.4/mod/mod_env.html).
