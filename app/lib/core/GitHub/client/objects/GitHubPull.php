<?php

require_once(__DIR__ . '/../GitHubObject.php');
require_once(__DIR__ . '/GitHubUser.php');
require_once(__DIR__ . '/GitHubPullLinks.php');
	

class GitHubPull extends GitHubObject
{
	/* (non-PHPdoc)
	 * @see GitHubObject::getAttributes()
	 */
	protected function getAttributes()
	{
		return array_merge(parent::getAttributes(), array(
			'url' => 'string',
			'html_url' => 'string',
			'diff_url' => 'string',
			'patch_url' => 'string',
			'issue_url' => 'string',
			'number' => 'int',
			'state' => 'string',
			'title' => 'string',
			'body' => 'string',
			'created_at' => 'string',
			'updated_at' => 'string',
			'closed_at' => 'string',
			'merged_at' => 'string',
			'user' => 'GitHubUser',
			'links' => 'GitHubPullLinks',
		));
	}
	
	/**
	 * @var string
	 */
	protected $url;

	/**
	 * @var string
	 */
	protected $html_url;

	/**
	 * @var string
	 */
	protected $diff_url;

	/**
	 * @var string
	 */
	protected $patch_url;

	/**
	 * @var string
	 */
	protected $issue_url;

	/**
	 * @var int
	 */
	protected $number;

	/**
	 * @var string
	 */
	protected $state;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var string
	 */
	protected $body;

	/**
	 * @var string
	 */
	protected $created_at;

	/**
	 * @var string
	 */
	protected $updated_at;

	/**
	 * @var string
	 */
	protected $closed_at;

	/**
	 * @var string
	 */
	protected $merged_at;

	/**
	 * @var GitHubUser
	 */
	protected $user;

	/**
	 * @var GitHubPullLinks
	 */
	protected $links;

	/**
	 * @return string
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @return string
	 */
	public function getHtmlUrl()
	{
		return $this->html_url;
	}

	/**
	 * @return string
	 */
	public function getDiffUrl()
	{
		return $this->diff_url;
	}

	/**
	 * @return string
	 */
	public function getPatchUrl()
	{
		return $this->patch_url;
	}

	/**
	 * @return string
	 */
	public function getIssueUrl()
	{
		return $this->issue_url;
	}

	/**
	 * @return int
	 */
	public function getNumber()
	{
		return $this->number;
	}

	/**
	 * @return string
	 */
	public function getState()
	{
		return $this->state;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @return string
	 */
	public function getBody()
	{
		return $this->body;
	}

	/**
	 * @return string
	 */
	public function getCreatedAt()
	{
		return $this->created_at;
	}

	/**
	 * @return string
	 */
	public function getUpdatedAt()
	{
		return $this->updated_at;
	}

	/**
	 * @return string
	 */
	public function getClosedAt()
	{
		return $this->closed_at;
	}

	/**
	 * @return string
	 */
	public function getMergedAt()
	{
		return $this->merged_at;
	}

	/**
	 * @return GitHubUser
	 */
	public function getUser()
	{
		return $this->user;
	}

	/**
	 * @return GitHubPullLinks
	 */
	public function getLinks()
	{
		return $this->links;
	}

}

