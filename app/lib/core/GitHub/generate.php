<?php

chdir(__DIR__);

if(!file_exists(__DIR__ . '/client'))
	mkdir(__DIR__ . '/client');
if(!file_exists(__DIR__ . '/client/services'))
	mkdir(__DIR__ . '/client/services');
if(!file_exists(__DIR__ . '/client/objects'))
	mkdir(__DIR__ . '/client/objects');
	
$resources = str_replace("\r", '', file_get_contents(__DIR__ . '/resources.rb'));

$sourceDir = dir(__DIR__ . '/source');
while (false !== ($entry = $sourceDir->read())) 
{
	if($entry[0] != '.')
		copy(__DIR__ . '/source/' . $entry, __DIR__ . '/client/' . $entry);
}
$sourceDir->close();

$classTree = array();
$objects = array();
scanDirectory(realpath('v3'));
generateGithubClient();
generateGithubObjects();
generateGithubDoc();

function scanDirectory($dir, $classPath = array())
{
	global $classTree;
	
	$d = dir($dir);
	echo "Scanning directory: " . $d->path . "\n";
	while (false !== ($entry = $d->read())) 
	{
		if($entry[0] == '.')
			continue;

		$entryPath = $d->path . DIRECTORY_SEPARATOR . $entry;
		$entryClassPath = $classPath;
		$entryClassPath[] = preg_replace('/.md$/', '', $entry);
		if(is_dir($entryPath))
		{
			scanDirectory($entryPath, $entryClassPath);
		}
		else
		{
			$entryName = implode('', array_map('ucfirst', $entryClassPath));
			$parentClassPath = '/' . implode('/', $classPath);
			if(!isset($classTree[$parentClassPath]))
				$classTree[$parentClassPath] = array();
				
			$classTree[$parentClassPath][$entryPath] = $entryName;
		}
	}
	$d->close();
}

function generateGithubDoc()
{
	global $classTree;
	
	$doc = "
---
#GitHub API PHP Client
---

";

	foreach($classTree['/'] as $file => $className)
	{	
		$varName = lcfirst($className);
		$doc .= "
## GitHub$className
Could be access directly from GitHubClient->$varName
";
		$doc .= appendGithubServiceDoc($file, $className);
	}
	
	file_put_contents(__DIR__ . "/client.md", $doc);
}

function appendGithubServiceDoc($file, $name)
{
	global $classTree, $objects;
	
	$classPath = str_replace(array(__DIR__, '.md', '\\v3', '\\'), array('', '', '', '/'), $file);
	echo "Generating service doc: $name [$classPath]\n";
	$content = file_get_contents($file);
'
## List your notifications

List all notifications for the current user, grouped by repository.

    GET /notifications
';
	
	preg_match_all('/## ([^\n]+)\n\n(.*)(\n\n)?    (GET|PUT|PATCH|DELETE) ([^\n]+)\n\n(([^\n]+\n)+\n)?(### (Parameters|Input)\n\n([^#]+))?### Response\n(\n<%= headers (\d+) %>)?(\n<%= json( :([^\s]+) |\(:([^\)]+)\) [^%]*)%>)?\n\n/sU', $content, $matches);

	$doc = '
### Attributes:
';

	if(isset($classTree[$classPath]))
	{
		foreach($classTree[$classPath] as $file => $className)
		{
			$varName = lcfirst(preg_replace("/^$name/", '', $className));
			$doc .= "
 - GitHub$className $varName";
		}
	
	$doc .= '

### Sub-services:
';
	
		foreach($classTree[$classPath] as $file => $className)
		{
			$varName = lcfirst(preg_replace("/^$name/", '', $className));
			$doc .= "
 - GitHub$className $varName";
		}
	}
	
	$doc .= '

### Methods:
';
	
	foreach($matches[1] as $index => $description)
	{
		$methodName = lcfirst(str_replace(array(' A ', ' '), array('', ''), ucwords(preg_replace('/[^\w]/', ' ', strtolower($description)))));
		if($methodName == 'list')
			$methodName .= $name;
			
		$httpMethod = $matches[4][$index];
		$url = str_replace(':', '$', $matches[5][$index]);
		$arguments = array();
		$dataArguments = array();
		if(preg_match_all('/(\$[^\/?.]+)/', $url, $argumentsMatches))
			$arguments = $argumentsMatches[1];
		
		$paremetersDescription = $matches[10][$index];
		$docCommentParameters = array();
		$paremetersMatches = null;
		if($paremetersDescription && preg_match_all('/([^\n]+)\n: _([^_]+)_ \*\*([^\*]+)\*\* (.+)\n\n/sU', $paremetersDescription, $paremetersMatches))
		{
			foreach($paremetersMatches[1] as $parameterIndex => $parameterName)
			{
				$parameterName = preg_replace('/[^\w]/', '', $parameterName);
				$parameterRequirement = $paremetersMatches[2][$parameterIndex];
				$parameterType = $paremetersMatches[3][$parameterIndex];
				$parameterDescription = $paremetersMatches[4][$parameterIndex];
				$parameterDescription = implode("\n	 * \t", explode("\n", $parameterDescription));
				$docCommentParameters[] = "$parameterType parameterName ($parameterRequirement) $parameterDescription";
				$argument = "\$$parameterName";
				$dataArguments[] = $parameterName;
				if($parameterRequirement == 'Optional')
					$argument .= ' = null';
					
				$arguments[] = $argument;
			}
		}
		
		$expectedStatus = 200;
		if(isset($matches[12][$index]) && is_numeric($matches[12][$index]))
			$expectedStatus = $matches[12][$index];
		
		$arguments = implode(', ', $arguments);
		$doc .= "

**$methodName:**

Expected HTTP status: $expectedStatus
*$description*


Attributes:
";
		
		foreach($docCommentParameters as $docCommentParameter)
		{
			$doc .= "
 - $docCommentParameter";
		}
						
		$responseType = null;
		$returnType = null;
		$returnArray = false;
		
		if(isset($matches[15][$index]) && strlen($matches[15][$index]))
		{
			$responseType = $matches[15][$index];
		}
		elseif(isset($matches[16][$index]) && strlen($matches[16][$index]))
		{
			$responseType = $matches[16][$index];
			$returnArray = true;
		}
	
		if($responseType)
		{
			$objects[strtolower($responseType)] = true;
			$returnType = gitHubClassName($responseType);
			
			if($returnArray)
			{
			$doc .= "

Returns array of $returnType objects";
			}
			else 
			{
			$doc .= "

Returns $returnType object";
			}
		}
	}

	if(isset($classTree[$classPath]))
	{	
		foreach($classTree[$classPath] as $file => $className)
		{
			$varName = lcfirst($className);
			$doc .= "
## GitHub$className
Could be access directly from GitHubClient->{$name}->{$varName}
";
			$doc .= appendGithubServiceDoc($file, $className);
		}
	}

	return $doc;
}


function generateGithubClient()
{
	global $classTree;
	
	$requires = array();
	
	$class = "
class GitHubClient extends GitHubClientBase
{
";

	foreach($classTree['/'] as $file => $className)
	{
		generateGithubService($file, $className);
		$requires["GitHub$className"] = "require_once(__DIR__ . '/services/GitHub$className.php');";
		$varName = lcfirst($className);
		$class .= "
	/**
	 * @var GitHub$className
	 */
	public \$$varName;
	";
	}
	
	$class .= "
	
	/**
	 * Initialize sub services
	 */
	public function __construct()
	{";
		
	foreach($classTree['/'] as $file => $className)
	{
		$varName = lcfirst($className);
		$class .= "
		\$this->$varName = new GitHub$className(\$this);";
	}
		
	$class .= "
	}
	";
	
	$class .= "
}
";
	
	$requires = implode("\n", $requires);
	$php = "<?php

require_once(__DIR__ . '/GitHubClientBase.php');
$requires

$class
";

	file_put_contents(__DIR__ . "/client/GitHubClient.php", $php);
}

function generateGithubService($file, $name)
{
	global $classTree, $objects;
	
	$requires = array();
	$classPath = str_replace(array(__DIR__, '.md', '\\v3', '\\'), array('', '', '', '/'), $file);
	echo "Generating service: $name [$classPath]\n";
	$content = file_get_contents($file);
'
## List your notifications

List all notifications for the current user, grouped by repository.

    GET /notifications
';
	
	preg_match_all('/## ([^\n]+)\n\n(.*)(\n\n)?    (GET|PUT|PATCH|DELETE) ([^\n]+)\n\n(([^\n]+\n)+\n)?(### (Parameters|Input)\n\n([^#]+))?### Response\n(\n<%= headers (\d+) %>)?(\n<%= json( :([^\s]+) |\(:([^\)]+)\) [^%]*)%>)?\n\n/sU', $content, $matches);

	$class = "
class GitHub$name extends GitHubService
{
";

	if(isset($classTree[$classPath]))
	{
		foreach($classTree[$classPath] as $file => $className)
		{
			$requires["GitHub$className"] = "require_once(__DIR__ . '/GitHub$className.php');";
			$varName = lcfirst(preg_replace("/^$name/", '', $className));
			$class .= "
	/**
	 * @var GitHub$className
	 */
	public \$$varName;
	";
		}
	
		$class .= "
	
	/**
	 * Initialize sub services
	 */
	public function __construct(GitHubClient \$client)
	{
		parent::__construct(\$client);
		";
		
		foreach($classTree[$classPath] as $file => $className)
		{
			$varName = lcfirst(preg_replace("/^$name/", '', $className));
			$class .= "
		\$this->$varName = new GitHub$className(\$client);";
		}
		
		$class .= "
	}
	";
	}
	
	foreach($matches[1] as $index => $description)
	{
		$methodName = lcfirst(str_replace(array(' A ', ' '), array('', ''), ucwords(preg_replace('/[^\w]/', ' ', strtolower($description)))));
		if($methodName == 'list')
			$methodName .= $name;
			
		$httpMethod = $matches[4][$index];
		$url = str_replace(':', '$', $matches[5][$index]);
		$arguments = array();
		$dataArguments = array();
		if(preg_match_all('/(\$[^\/?.]+)/', $url, $argumentsMatches))
			$arguments = $argumentsMatches[1];
		
		$paremetersDescription = $matches[10][$index];
		$docCommentParameters = array();
		$paremetersMatches = null;
		if($paremetersDescription && preg_match_all('/([^\n]+)\n: _([^_]+)_ \*\*([^\*]+)\*\* (.+)\n\n/sU', $paremetersDescription, $paremetersMatches))
		{
			foreach($paremetersMatches[1] as $parameterIndex => $parameterName)
			{
				$parameterName = preg_replace('/[^\w]/', '', $parameterName);
				$parameterRequirement = $paremetersMatches[2][$parameterIndex];
				$parameterType = $paremetersMatches[3][$parameterIndex];
				$parameterDescription = $paremetersMatches[4][$parameterIndex];
				$parameterDescription = implode("\n	 * \t", explode("\n", $parameterDescription));
				$docCommentParameters[] = "\$$parameterName $parameterType ($parameterRequirement) $parameterDescription";
				$argument = "\$$parameterName";
				$dataArguments[] = $parameterName;
				if($parameterRequirement == 'Optional')
					$argument .= ' = null';
					
				$arguments[] = $argument;
			}
		}
		
		$expectedStatus = 200;
		if(isset($matches[12][$index]) && is_numeric($matches[12][$index]))
			$expectedStatus = $matches[12][$index];
		
		$arguments = implode(', ', $arguments);
		$class .= "
	/**
	 * $description
	 * ";
		
		foreach($docCommentParameters as $docCommentParameter)
		{
			$class .= "
	 * @param $docCommentParameter";
		}
						
		$responseType = null;
		$returnType = null;
		$returnArray = false;
		
		if(isset($matches[15][$index]) && strlen($matches[15][$index]))
		{
			$responseType = $matches[15][$index];
		}
		elseif(isset($matches[16][$index]) && strlen($matches[16][$index]))
		{
			$responseType = $matches[16][$index];
			$returnArray = true;
		}
	
		if($responseType)
		{
			$objects[strtolower($responseType)] = true;
			$returnType = gitHubClassName($responseType);
			$requires[$returnType] = "require_once(__DIR__ . '/../objects/$returnType.php');";
			
			if($returnArray)
			{
				$class .= "
	 * @return array<$returnType>";
			}
			else 
			{
				$class .= "
	 * @return $returnType";
			}
		}
	 
		$class .= "
	 */
	public function $methodName($arguments)
	{
		\$data = array();";
		
		foreach($dataArguments as $dataArgument)
		{
			$class .= "
		if(!is_null(\$$dataArgument))
			\$data['$dataArgument'] = \$$dataArgument;";
		}
		
		$class .= "
		
		return \$this->client->request(\"$url\", '$httpMethod', \$data, $expectedStatus, '$returnType'" . ($returnArray ? ', true' : '') . ");
	}
	";
	}
	
	$class .= "
}
";

	$requires = implode("\n", $requires);
	$php = "<?php

require_once(__DIR__ . '/../GitHubClient.php');
require_once(__DIR__ . '/../GitHubService.php');
$requires
	
$class
";

	file_put_contents(__DIR__ . "/client/services/GitHub$name.php", $php);

	if(isset($classTree[$classPath]))
	{	
		foreach($classTree[$classPath] as $file => $className)
			generateGithubService($file, $className);
	}
}

function generateGithubObject($className, array $attributes, $extends = null)
{
	echo "Generating object: $className\n";
	if(is_null($extends))
		$extends = 'GitHubObject';
		
	$requires = array();
	$class = "
class $className extends GitHubObject
{
	/* (non-PHPdoc)
	 * @see GitHubObject::getAttributes()
	 */
	protected function getAttributes()
	{
		return array_merge(parent::getAttributes(), array(";

	foreach($attributes as $attributeName => $attributeType)
	{
		$class .= "
			'$attributeName' => '$attributeType',";
	}
	
	$class .= "
		));
	}
	";
	
	foreach($attributes as $attributeName => $attributeType)
	{
		$matches = null;
		if(preg_match('/^(GitHub.+)$/', $attributeType, $matches) || preg_match('/^array<(GitHub.+)>$/', $attributeType, $matches))
			$requires[$attributeType] = "require_once(__DIR__ . '/" . $matches[1] . ".php');";
			
		$class .= "
	/**
	 * @var $attributeType
	 */
	protected \$$attributeName;
";
	}
	
	foreach($attributes as $attributeName => $attributeType)
	{
		$getterName = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($attributeName))));
		
		if(preg_match('/^GitHub/', $attributeType))
			$requires[$attributeType] = "require_once(__DIR__ . '/$attributeType.php');";
			
		$class .= "
	/**
	 * @return $attributeType
	 */
	public function $getterName()
	{
		return \$this->$attributeName;
	}
";
	}
	
	$class .= "
}
";
	
	if($extends == 'GitHubObject')
		$extends = '../GitHubObject';
		
	$requires = implode("\n", $requires);
	$php = "<?php

require_once(__DIR__ . '/$extends.php');
$requires
	
$class
";

	file_put_contents(__DIR__ . "/client/objects/$className.php", $php);
}

function gitHubClassName($baseName)
{
	return 'GitHub' . str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($baseName))));
}

function parseAttributes($resourceName, $resource, $indent = '      ')
{
	global $objects;
	
	$attributes = array();
	
	$matches = null;
	if(preg_match_all('/\n' . $indent . '[:"]([\w_]+)"? +=> +([^\{\[].+),?/', $resource, $matches))
	{
		foreach($matches[1] as $index => $attributeName)
		{
			$value = trim($matches[2][$index], ' ,');
			if($value[0] == '{')
				continue;
				
			$attributeType = 'string';
			if(preg_match('/^[A-Z_]+$/', $value))
			{
				$attributeTypeName = preg_replace('/^_/', '', $value);
				$attributeType = gitHubClassName($attributeTypeName);
				$objects[strtolower($value)] = true;
			}
			elseif($value == 'true' || $value == 'false')
			{
				$attributeType = 'boolean';
			}
			elseif(preg_match('/^\d+$/', $value))
			{
					$attributeType = 'int';
			}
				
			$attributes[$attributeName] = $attributeType;
		}
	}

	if(preg_match_all('/\n' . $indent . '[:"]([\w_]+)"? => \{(.+)\n' . $indent . '\}/sU', $resource, $matches))
	{
		foreach($matches[1] as $index => $attributeName)
		{
			$attributeName = preg_replace('/^_/', '', $attributeName);
			$attributeResourceName = "$resourceName $attributeName";
			$attributeType = gitHubClassName($attributeResourceName);
			$attributes[$attributeName] = $attributeType;
			$attributeAttributes = parseAttributes($attributeResourceName, $matches[2][$index], "$indent  ");
			generateGithubObject($attributeType, $attributeAttributes);
		}
	}
	
	if(preg_match_all('/\n' . $indent . '[:"]([\w_]+)"? => \[ *\{(.+)\n' . $indent . '\}?\]/sU', $resource, $matches))
	{
		foreach($matches[1] as $index => $attributeName)
		{
			$attributeName = preg_replace('/^_/', '', $attributeName);
			$attributeResourceName = "$resourceName $attributeName";
			$attributeType = gitHubClassName($attributeResourceName);
			$attributes[$attributeName] = "array<$attributeType>";
			$attributeAttributes = parseAttributes($attributeResourceName, $matches[2][$index], "$indent  ");
			generateGithubObject($attributeType, $attributeAttributes);
		}
	}
	
	return $attributes;
}

function getObjectAttributes($resourceName, &$extends, $enableExtend = true)
{
	global $resources, $objects;
	
	$matches = null;
	if(
		!preg_match('/\n    ' . $resourceName . ' = (\{|(\w+)\.merge \\\\)(.+)\n    [^ ]/sU', $resources, $matches)
		&&
		!preg_match('/\n    ' . $resourceName . ' = ((\w+))((\.merge\([^\(]+\))+)/', $resources, $matches)
	)
	{
		echo "Cant find resource for object [$resourceName]\n";
		return array();
	}
		
	$attributes = array();
	if($matches[2])
	{
		if($enableExtend)
		{
			$objects[strtolower($matches[2])] = true;
			$extends = gitHubClassName($matches[2]);
		}
		else
		{
			$attributes = getObjectAttributes($matches[2], $extends, $enableExtend);
		}
	}
		
	$mergeMatches = null;
	if(preg_match_all('/\.merge\((\'[^\']+\' => )?([^\)]+)\)/', $matches[3], $mergeMatches))
	{
		foreach($mergeMatches[2] as $mergeResource)
		{
			$mergeAttributesMatches = null;
			if(preg_match('/^\{(\n( +).+)\}$/s', $mergeResource, $mergeAttributesMatches))
			{
				$mergeAttributes = parseAttributes($resourceName, $mergeAttributesMatches[1], $mergeAttributesMatches[2]);
				$attributes = array_merge($attributes, $mergeAttributes);
			}
			else
			{
				echo "Merging $mergeResource into $resourceName\n";
				$attributes = array_merge($attributes, getObjectAttributes($mergeResource, $extends, false));
			}
		}
	}
	else
	{
		$attributes = array_merge($attributes, parseAttributes($resourceName, $matches[3]));
	}
	return $attributes;
}

function generateGithubObjects()
{
	global $objects;
	
	foreach($objects as $object => $true)
	{
		$resourceName = strtoupper($object);
		$extends = null;
		$attributes = getObjectAttributes($resourceName, $extends);
		generateGithubObject(gitHubClassName($object), $attributes, $extends);
	}
}
