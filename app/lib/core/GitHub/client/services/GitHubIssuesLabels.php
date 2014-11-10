<?php

require_once(__DIR__ . '/../GitHubClient.php');
require_once(__DIR__ . '/../GitHubService.php');
require_once(__DIR__ . '/../objects/GitHubLabel.php');
	

class GitHubIssuesLabels extends GitHubService
{

	/**
	 * List all labels for this repository
	 * 
	 * @return array<GitHubLabel>
	 */
	public function listAllLabelsForThisRepository($owner, $repo)
	{
		$data = array();
		
		return $this->client->request("/repos/$owner/$repo/labels", 'GET', $data, 200, 'GitHubLabel', true);
	}
	
	/**
	 * Get a single label
	 * 
	 * @return GitHubLabel
	 */
	public function getSingleLabel($owner, $repo, $name)
	{
		$data = array();
		
		return $this->client->request("/repos/$owner/$repo/labels/$name", 'GET', $data, 200, 'GitHubLabel');
	}
	
	/**
	 * Create a label
	 * 
	 */
	public function createLabel($owner, $repo, $name)
	{
		$data = array();
		
		return $this->client->request("/repos/$owner/$repo/labels/$name", 'DELETE', $data, 204, '');
	}
	
	/**
	 * List labels on an issue
	 * 
	 * @return array<GitHubLabel>
	 */
	public function listLabelsOnAnIssue($owner, $repo, $number)
	{
		$data = array();
		
		return $this->client->request("/repos/$owner/$repo/issues/$number/labels", 'GET', $data, 200, 'GitHubLabel', true);
	}
	
	/**
	 * Add labels to an issue
	 * 
	 */
	public function addLabelsToAnIssue($owner, $repo, $number, $name)
	{
		$data = array();
		
		return $this->client->request("/repos/$owner/$repo/issues/$number/labels/$name", 'DELETE', $data, 204, '');
	}
	
	/**
	 * Replace all labels for an issue
	 * 
	 * @return array<GitHubLabel>
	 */
	public function replaceAllLabelsForAnIssue($owner, $repo, $number)
	{
		$data = array();
		
		return $this->client->request("/repos/$owner/$repo/issues/$number/labels", 'PUT', $data, 200, 'GitHubLabel', true);
	}
	
	/**
	 * Remove all labels from an issue
	 * 
	 */
	public function removeAllLabelsFromAnIssue($owner, $repo, $number)
	{
		$data = array();
		
		return $this->client->request("/repos/$owner/$repo/issues/$number/labels", 'DELETE', $data, 204, '');
	}
	
}

