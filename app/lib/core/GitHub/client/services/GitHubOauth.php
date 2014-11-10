<?php

require_once(__DIR__ . '/../GitHubClient.php');
require_once(__DIR__ . '/../GitHubService.php');
require_once(__DIR__ . '/../objects/GitHubOauthAccess.php');
require_once(__DIR__ . '/../objects/GitHubOauthAccessWithUser.php');
	

class GitHubOauth extends GitHubService
{

	/**
	 * Web Application Flow
	 * 
	 * @return GitHubOauthAccess
	 */
	public function webApplicationFlow($id)
	{
		$data = array();
		
		return $this->client->request("/authorizations/$id", 'GET', $data, 200, 'GitHubOauthAccess');
	}
	
	/**
	 * Create a new authorization
	 * 
	 * @param $scopes array (Optional) - Replaces the authorization scopes with these.
	 * @param $add_scopes array (Optional) - A list of scopes to add to this authorization.
	 * @param $remove_scopes array (Optional) - A list of scopes to remove from this
	 * 	authorization.
	 * @param $note string (Optional) - A note to remind you what the OAuth token is for.
	 * @param $note_url string (Optional) - A URL to remind you what app the OAuth token is for.
	 * @return GitHubOauthAccess
	 */
	public function createNewAuthorization($id, $scopes = null, $add_scopes = null, $remove_scopes = null, $note = null, $note_url = null)
	{
		$data = array();
		if(!is_null($scopes))
			$data['scopes'] = $scopes;
		if(!is_null($add_scopes))
			$data['add_scopes'] = $add_scopes;
		if(!is_null($remove_scopes))
			$data['remove_scopes'] = $remove_scopes;
		if(!is_null($note))
			$data['note'] = $note;
		if(!is_null($note_url))
			$data['note_url'] = $note_url;
		
		return $this->client->request("/authorizations/$id", 'PATCH', $data, 200, 'GitHubOauthAccess');
	}
	
	/**
	 * Delete an authorization
	 * 
	 */
	public function deleteAnAuthorization($id)
	{
		$data = array();
		
		return $this->client->request("/authorizations/$id", 'DELETE', $data, 204, '');
	}
	
	/**
	 * Check an authorization
	 * 
	 * @return array<GitHubOauthAccessWithUser>
	 */
	public function checkAnAuthorization($client_id, $access_token)
	{
		$data = array();
		
		return $this->client->request("/applications/$client_id/tokens/$access_token", 'GET', $data, 200, 'GitHubOauthAccessWithUser', true);
	}
	
}

