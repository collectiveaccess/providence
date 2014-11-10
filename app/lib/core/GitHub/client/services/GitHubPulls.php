<?php

require_once(__DIR__ . '/../GitHubClient.php');
require_once(__DIR__ . '/../GitHubService.php');
require_once(__DIR__ . '/GitHubPullsComments.php');
require_once(__DIR__ . '/../objects/GitHubPull.php');
require_once(__DIR__ . '/../objects/GitHubFullPull.php');
require_once(__DIR__ . '/../objects/GitHubCommit.php');
require_once(__DIR__ . '/../objects/GitHubFile.php');
	

class GitHubPulls extends GitHubService
{

	/**
	 * @var GitHubPullsComments
	 */
	public $comments;
	
	
	/**
	 * Initialize sub services
	 */
	public function __construct(GitHubClient $client)
	{
		parent::__construct($client);
		
		$this->comments = new GitHubPullsComments($client);
	}
	
	/**
	 * Link Relations
	 * 
	 * @param $state string (Optional) - `open` or `closed` to filter by state. Default
	 * 	is `open`.
	 * @param $head string (Optional) - Filter pulls by head user and branch name in the format
	 * 	of: `user:ref-name`. Example: `github:new-script-format`.
	 * @param $base string (Optional) - Filter pulls by base branch name. Example:
	 * 	`gh-pages`.
	 * @return array<GitHubPull>
	 */
	public function linkRelations($owner, $repo, $state = null, $head = null, $base = null)
	{
		$data = array();
		if(!is_null($state))
			$data['state'] = $state;
		if(!is_null($head))
			$data['head'] = $head;
		if(!is_null($base))
			$data['base'] = $base;
		
		return $this->client->request("/repos/$owner/$repo/pulls", 'GET', $data, 200, 'GitHubPull', true);
	}
	
	/**
	 * Get a single pull request
	 * 
	 * @return GitHubFullPull
	 */
	public function getSinglePullRequest($owner, $repo, $number)
	{
		$data = array();
		
		return $this->client->request("/repos/$owner/$repo/pulls/$number", 'GET', $data, 200, 'GitHubFullPull');
	}
	
	/**
	 * Mergability
	 * 
	 * @param $state string (Optional) - State of this Pull Request. Valid values are
	 * 	`open` and `closed`.
	 * @return GitHubPull
	 */
	public function mergability($owner, $repo, $number, $state = null)
	{
		$data = array();
		if(!is_null($state))
			$data['state'] = $state;
		
		return $this->client->request("/repos/$owner/$repo/pulls/$number", 'PATCH', $data, 200, 'GitHubPull');
	}
	
	/**
	 * List commits on a pull request
	 * 
	 * @return array<GitHubCommit>
	 */
	public function listCommitsOnPullRequest($owner, $repo, $number)
	{
		$data = array();
		
		return $this->client->request("/repos/$owner/$repo/pulls/$number/commits", 'GET', $data, 200, 'GitHubCommit', true);
	}
	
	/**
	 * List pull requests files
	 * 
	 * @return array<GitHubFile>
	 */
	public function listPullRequestsFiles($owner, $repo, $number)
	{
		$data = array();
		
		return $this->client->request("/repos/$owner/$repo/pulls/$number/files", 'GET', $data, 200, 'GitHubFile', true);
	}
	
	/**
	 * Get if a pull request has been merged
	 *
	 * @return boolean
	 */
	public function isPullRequestMerged($owner, $repo, $number)
	{
		$merged = false;

		try
		{
			$data = array();
			$this->client->request("/repos/$owner/$repo/pulls/$number/merge", 'GET', $data, 204, '');
			$merged = true;
		}
		catch ( GitHubClientException $e )
		{
		}

		return $merged;
	}
}

