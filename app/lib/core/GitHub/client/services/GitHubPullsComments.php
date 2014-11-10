<?php

require_once(__DIR__ . '/../GitHubClient.php');
require_once(__DIR__ . '/../GitHubService.php');
require_once(__DIR__ . '/../objects/GitHubPullComment.php');
	

class GitHubPullsComments extends GitHubService
{

	/**
	 * List comments on a pull request
	 * 
	 * @return array<GitHubPullComment>
	 */
	public function listCommentsOnPullRequest($owner, $repo, $number)
	{
		$data = array();
		
		return $this->client->request("/repos/$owner/$repo/pulls/$number/comments", 'GET', $data, 200, 'GitHubPullComment', true);
	}
	
	/**
	 * List comments in a repository
	 * 
	 * @param $sort String (Optional) `created` or `updated`
	 * @param $direction String (Optional) `asc` or `desc`. Ignored without `sort` parameter.
	 * @param $since String (Optional) of a timestamp in ISO 8601 format: YYYY-MM-DDTHH:MM:SSZ
	 * @return array<GitHubPullComment>
	 */
	public function listCommentsInRepository($owner, $repo, $sort = null, $direction = null, $since = null)
	{
		$data = array();
		if(!is_null($sort))
			$data['sort'] = $sort;
		if(!is_null($direction))
			$data['direction'] = $direction;
		if(!is_null($since))
			$data['since'] = $since;
		
		return $this->client->request("/repos/$owner/$repo/pulls/comments", 'GET', $data, 200, 'GitHubPullComment', true);
	}
	
	/**
	 * Get a single comment
	 * 
	 * @return GitHubPullComment
	 */
	public function getSingleComment($owner, $repo, $number)
	{
		$data = array();
		
		return $this->client->request("/repos/$owner/$repo/pulls/comments/$number", 'GET', $data, 200, 'GitHubPullComment');
	}
	
	/**
	 * Create a comment
	 * 
	 */
	public function createComment($owner, $repo, $number)
	{
		$data = array();
		
		return $this->client->request("/repos/$owner/$repo/pulls/comments/$number", 'DELETE', $data, 204, '');
	}
	
}

