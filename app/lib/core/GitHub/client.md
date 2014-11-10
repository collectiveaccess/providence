
---
#GitHub API PHP Client
---


## GitHubActivity
Could be access directly from GitHubClient->activity

### Attributes:

 - GitHubActivityEvents events
 - GitHubActivityFeeds feeds
 - GitHubActivityNotifications notifications
 - GitHubActivitySettings settings
 - GitHubActivityStarring starring
 - GitHubActivityWatching watching

### Sub-services:

 - GitHubActivityEvents events
 - GitHubActivityFeeds feeds
 - GitHubActivityNotifications notifications
 - GitHubActivitySettings settings
 - GitHubActivityStarring starring
 - GitHubActivityWatching watching

### Methods:

## GitHubActivityEvents
Could be access directly from GitHubClient->Activity->activityEvents

### Attributes:

 - GitHubActivityEventsTypes types

### Sub-services:

 - GitHubActivityEventsTypes types

### Methods:

## GitHubActivityEventsTypes
Could be access directly from GitHubClient->ActivityEvents->activityEventsTypes

### Attributes:


### Methods:

## GitHubActivityFeeds
Could be access directly from GitHubClient->Activity->activityFeeds

### Attributes:


### Methods:


**listFeeds:**

Expected HTTP status: 200
*List Feeds*


Attributes:


Returns GitHubFeeds object
## GitHubActivityNotifications
Could be access directly from GitHubClient->Activity->activityNotifications

### Attributes:


### Methods:


**listYourNotifications:**

Expected HTTP status: 200
*List your notifications*


Attributes:

 - boolean parameterName (Optional) `true` to show notifications marked as read.
 - boolean parameterName (Optional) `true` to show only notifications in which the user is
	 * 	directly participating or mentioned.
 - time parameterName (Optional) filters out any notifications updated before the given
	 * 	time.  The time should be passed in as UTC in the ISO 8601 format:
	 * 	`YYYY-MM-DDTHH:MM:SSZ`.  Example: "2012-10-09T23:39:01Z".

Returns array of GitHubThread objects

**listYourNotificationsInRepository:**

Expected HTTP status: 200
*List your notifications in a repository*


Attributes:

 - boolean parameterName (Optional) `true` to show notifications marked as read.
 - boolean parameterName (Optional) `true` to show only notifications in which the user is
	 * 	directly participating or mentioned.
 - time parameterName (Optional) filters out any notifications updated before the given
	 * 	time.  The time should be passed in as UTC in the ISO 8601 format:
	 * 	`YYYY-MM-DDTHH:MM:SSZ`.  Example: "2012-10-09T23:39:01Z".

Returns array of GitHubThread objects

**markAsRead:**

Expected HTTP status: 205
*Mark as read*


Attributes:

 - Time parameterName (Optional) Describes the last point that notifications were checked.  Anything
	 * 	updated since this time will not be updated.  Default: Now.  Expected in ISO
	 * 	8601 format: `YYYY-MM-DDTHH:MM:SSZ`.  Example: "2012-10-09T23:39:01Z".

**markNotificationsAsReadInRepository:**

Expected HTTP status: 205
*Mark notifications as read in a repository*


Attributes:

 - Time parameterName (Optional) Describes the last point that notifications were checked.  Anything
	 * 	updated since this time will not be updated.  Default: Now.  Expected in ISO
	 * 	8601 format: `YYYY-MM-DDTHH:MM:SSZ`.  Example: "2012-10-09T23:39:01Z".

**viewSingleThread:**

Expected HTTP status: 200
*View a single thread*


Attributes:


Returns array of GitHubThread objects

**markThreadAsRead:**

Expected HTTP status: 205
*Mark a thread as read*


Attributes:


**getThreadSubscription:**

Expected HTTP status: 200
*Get a Thread Subscription*


Attributes:


Returns GitHubSubscription object

**setThreadSubscription:**

Expected HTTP status: 200
*Set a Thread Subscription*


Attributes:


Returns GitHubSubscription object

**deleteThreadSubscription:**

Expected HTTP status: 204
*Delete a Thread Subscription*


Attributes:

## GitHubActivitySettings
Could be access directly from GitHubClient->Activity->activitySettings

### Attributes:


### Methods:

## GitHubActivityStarring
Could be access directly from GitHubClient->Activity->activityStarring

### Attributes:


### Methods:


**listStargazers:**

Expected HTTP status: 204
*List Stargazers*


Attributes:

## GitHubActivityWatching
Could be access directly from GitHubClient->Activity->activityWatching

### Attributes:


### Methods:


**listWatchers:**

Expected HTTP status: 200
*List watchers*


Attributes:


Returns GitHubRepoSubscription object

**setRepositorySubscription:**

Expected HTTP status: 200
*Set a Repository Subscription*


Attributes:


Returns GitHubRepoSubscription object

**deleteRepositorySubscription:**

Expected HTTP status: 204
*Delete a Repository Subscription*


Attributes:


**checkIfYouAreWatchingRepositoryLegacy:**

Expected HTTP status: 204
*Check if you are watching a repository (LEGACY)*


Attributes:

## GitHubChangelog
Could be access directly from GitHubClient->changelog

### Attributes:


### Methods:

## GitHubGists
Could be access directly from GitHubClient->gists

### Attributes:

 - GitHubGistsComments comments

### Sub-services:

 - GitHubGistsComments comments

### Methods:


**authentication:**

Expected HTTP status: 200
*Authentication*


Attributes:


Returns GitHubFullGist object

**createGist:**

Expected HTTP status: 200
*Create a gist*


Attributes:

 - hash parameterName (Optional) - Files that make up this gist. The key of which
	 * 	should be an _optional_ **string** filename and the value another
	 * 	_optional_ **hash** with parameters:

Returns GitHubFullGist object

**starGist:**

Expected HTTP status: 204
*Star a gist*


Attributes:


**unstarGist:**

Expected HTTP status: 204
*Unstar a gist*


Attributes:


**checkIfGistIsStarred:**

Expected HTTP status: 204
*Check if a gist is starred*


Attributes:

## GitHubGistsComments
Could be access directly from GitHubClient->Gists->gistsComments

### Attributes:


### Methods:


**listCommentsOnGist:**

Expected HTTP status: 200
*List comments on a gist*


Attributes:


Returns array of GitHubGistComment objects

**getSingleComment:**

Expected HTTP status: 200
*Get a single comment*


Attributes:


Returns GitHubGistComment object

**createComment:**

Expected HTTP status: 200
*Create a comment*


Attributes:


Returns GitHubGistComment object

**deleteComment:**

Expected HTTP status: 204
*Delete a comment*


Attributes:

## GitHubGit
Could be access directly from GitHubClient->git

### Attributes:

 - GitHubGitBlobs blobs
 - GitHubGitCommits commits
 - GitHubGitImport import
 - GitHubGitRefs refs
 - GitHubGitTags tags
 - GitHubGitTrees trees

### Sub-services:

 - GitHubGitBlobs blobs
 - GitHubGitCommits commits
 - GitHubGitImport import
 - GitHubGitRefs refs
 - GitHubGitTags tags
 - GitHubGitTrees trees

### Methods:

## GitHubGitBlobs
Could be access directly from GitHubClient->Git->gitBlobs

### Attributes:


### Methods:


**getBlob:**

Expected HTTP status: 200
*Get a Blob*


Attributes:


Returns array of GitHubBlob objects
## GitHubGitCommits
Could be access directly from GitHubClient->Git->gitCommits

### Attributes:


### Methods:


**getCommit:**

Expected HTTP status: 200
*Get a Commit*


Attributes:


Returns GitHubGitCommit object
## GitHubGitImport
Could be access directly from GitHubClient->Git->gitImport

### Attributes:


### Methods:

## GitHubGitRefs
Could be access directly from GitHubClient->Git->gitRefs

### Attributes:


### Methods:


**getReference:**

Expected HTTP status: 200
*Get a Reference*


Attributes:


Returns GitHubRef object

**getAllReferences:**

Expected HTTP status: 204
*Get all References*


Attributes:

## GitHubGitTags
Could be access directly from GitHubClient->Git->gitTags

### Attributes:


### Methods:


**getTag:**

Expected HTTP status: 200
*Get a Tag*


Attributes:


Returns GitHubGittag object
## GitHubGitTrees
Could be access directly from GitHubClient->Git->gitTrees

### Attributes:


### Methods:


**getTree:**

Expected HTTP status: 200
*Get a Tree*


Attributes:


Returns GitHubTree object

**getTreeRecursively:**

Expected HTTP status: 200
*Get a Tree Recursively*


Attributes:


Returns GitHubTreeExtra object
## GitHubGitignore
Could be access directly from GitHubClient->gitignore

### Attributes:


### Methods:


**listingAvailableTemplates:**

Expected HTTP status: 200
*Listing available templates*


Attributes:


Returns array of GitHubTemplates objects

**getSingleTemplate:**

Expected HTTP status: 200
*Get a single template*


Attributes:


Returns array of GitHubTemplate objects
## GitHubIssues
Could be access directly from GitHubClient->issues

### Attributes:

 - GitHubIssuesAssignees assignees
 - GitHubIssuesComments comments
 - GitHubIssuesEvents events
 - GitHubIssuesLabels labels
 - GitHubIssuesMilestones milestones

### Sub-services:

 - GitHubIssuesAssignees assignees
 - GitHubIssuesComments comments
 - GitHubIssuesEvents events
 - GitHubIssuesLabels labels
 - GitHubIssuesMilestones milestones

### Methods:


**listIssues:**

Expected HTTP status: 200
*List issues*


Attributes:


Returns GitHubIssue object

**createAnIssue:**

Expected HTTP status: 200
*Create an issue*


Attributes:

 - string parameterName (Optional) - Login for the user that this issue should be
	 * 	assigned to.
 - string parameterName (Optional) - State of the issue: `open` or `closed`.
 - number parameterName (Optional) - Milestone to associate this issue with.
 - array parameterName (Optional) of **strings** - Labels to associate with this
	 * 	issue. Pass one or more Labels to _replace_ the set of Labels on this
	 * 	Issue. Send an empty array (`[]`) to clear all Labels from the Issue.

Returns GitHubIssue object
## GitHubIssuesAssignees
Could be access directly from GitHubClient->Issues->issuesAssignees

### Attributes:


### Methods:


**listAssignees:**

Expected HTTP status: 200
*List assignees*


Attributes:


Returns array of GitHubUser objects
## GitHubIssuesComments
Could be access directly from GitHubClient->Issues->issuesComments

### Attributes:


### Methods:


**listCommentsOnAnIssue:**

Expected HTTP status: 200
*List comments on an issue*


Attributes:

 - String parameterName (Optional) `created` or `updated`
 - String parameterName (Optional) `asc` or `desc`. Ignored without `sort` parameter.
 - String parameterName (Optional) of a timestamp in ISO 8601 format: YYYY-MM-DDTHH:MM:SSZ

Returns array of GitHubPullComment objects

**getSingleComment:**

Expected HTTP status: 200
*Get a single comment*


Attributes:


Returns GitHubIssueComment object

**createComment:**

Expected HTTP status: 200
*Create a comment*


Attributes:


Returns GitHubIssueComment object

**deleteComment:**

Expected HTTP status: 204
*Delete a comment*


Attributes:

## GitHubIssuesEvents
Could be access directly from GitHubClient->Issues->issuesEvents

### Attributes:


### Methods:


**attributes:**

Expected HTTP status: 200
*Attributes*


Attributes:


Returns GitHubFullIssueEvent object
## GitHubIssuesLabels
Could be access directly from GitHubClient->Issues->issuesLabels

### Attributes:


### Methods:


**listAllLabelsForThisRepository:**

Expected HTTP status: 200
*List all labels for this repository*


Attributes:


Returns array of GitHubLabel objects

**getSingleLabel:**

Expected HTTP status: 200
*Get a single label*


Attributes:


Returns GitHubLabel object

**createLabel:**

Expected HTTP status: 204
*Create a label*


Attributes:


**listLabelsOnAnIssue:**

Expected HTTP status: 200
*List labels on an issue*


Attributes:


Returns array of GitHubLabel objects

**addLabelsToAnIssue:**

Expected HTTP status: 204
*Add labels to an issue*


Attributes:


**replaceAllLabelsForAnIssue:**

Expected HTTP status: 200
*Replace all labels for an issue*


Attributes:


Returns array of GitHubLabel objects

**removeAllLabelsFromAnIssue:**

Expected HTTP status: 204
*Remove all labels from an issue*


Attributes:

## GitHubIssuesMilestones
Could be access directly from GitHubClient->Issues->issuesMilestones

### Attributes:


### Methods:


**listMilestonesForRepository:**

Expected HTTP status: 200
*List milestones for a repository*


Attributes:


Returns GitHubMilestone object

**createMilestone:**

Expected HTTP status: 200
*Create a milestone*


Attributes:

 - string parameterName (Optional) - `open` or `closed`. Default is `open`.
 - string parameterName (Optional) - ISO 8601 time.

Returns GitHubMilestone object

**deleteMilestone:**

Expected HTTP status: 204
*Delete a milestone*


Attributes:

## GitHubLibraries
Could be access directly from GitHubClient->libraries

### Attributes:


### Methods:

## GitHubMarkdown

### Attributes:


### Methods:

**getTextAsMarkdown:**

Expected HTTP status: 200
*Render an arbitrary Markdown document*

Attributes:
 - String text (Required) markdown text to process
 - String mode (Optional) `markdown` or `gfm`. Defaults to `markdown`
 - String context (Optional) the repository context. Only taken into account when rendering as gfm

Returns text/html 


## GitHubMedia
Could be access directly from GitHubClient->media

### Attributes:


### Methods:

## GitHubMeta
Could be access directly from GitHubClient->meta

### Attributes:


### Methods:

## GitHubOauth
Could be access directly from GitHubClient->oauth

### Attributes:


### Methods:


**webApplicationFlow:**

Expected HTTP status: 200
*Web Application Flow*


Attributes:


Returns GitHubOauthAccess object

**createNewAuthorization:**

Expected HTTP status: 200
*Create a new authorization*


Attributes:

 - array parameterName (Optional) - Replaces the authorization scopes with these.
 - array parameterName (Optional) - A list of scopes to add to this authorization.
 - array parameterName (Optional) - A list of scopes to remove from this
	 * 	authorization.
 - string parameterName (Optional) - A note to remind you what the OAuth token is for.
 - string parameterName (Optional) - A URL to remind you what app the OAuth token is for.

Returns GitHubOauthAccess object

**deleteAnAuthorization:**

Expected HTTP status: 204
*Delete an authorization*


Attributes:


**checkAnAuthorization:**

Expected HTTP status: 200
*Check an authorization*


Attributes:


Returns array of GitHubOauthAccessWithUser objects
## GitHubOrgs
Could be access directly from GitHubClient->orgs

### Attributes:

 - GitHubOrgsMembers members
 - GitHubOrgsTeams teams

### Sub-services:

 - GitHubOrgsMembers members
 - GitHubOrgsTeams teams

### Methods:


**listUserOrganizations:**

Expected HTTP status: 200
*List User Organizations*


Attributes:


Returns array of GitHubFullOrg objects
## GitHubOrgsMembers
Could be access directly from GitHubClient->Orgs->orgsMembers

### Attributes:


### Methods:


**membersList:**

Expected HTTP status: 200
*Members list*


Attributes:


Returns array of GitHubUser objects

**responseIfRequesterIsNotAnOrganizationMember:**

Expected HTTP status: 204
*Response if requester is not an organization member*


Attributes:


**publicMembersList:**

Expected HTTP status: 200
*Public members list*


Attributes:


Returns array of GitHubUser objects

**checkPublicMembership:**

Expected HTTP status: 204
*Check public membership*


Attributes:

## GitHubOrgsTeams
Could be access directly from GitHubClient->Orgs->orgsTeams

### Attributes:


### Methods:


**listTeams:**

Expected HTTP status: 200
*List teams*


Attributes:


Returns array of GitHubTeam objects

**getTeam:**

Expected HTTP status: 200
*Get team*


Attributes:


Returns array of GitHubFullTeam objects

**createTeam:**

Expected HTTP status: 200
*Create team*


Attributes:


Returns array of GitHubFullTeam objects

**deleteTeam:**

Expected HTTP status: 204
*Delete team*


Attributes:


**listTeamMembers:**

Expected HTTP status: 200
*List team members*


Attributes:


Returns array of GitHubUser objects

**getTeamMember:**

Expected HTTP status: 204
*Get team member*


Attributes:


**removeTeamMember:**

Expected HTTP status: 204
*Remove team member*


Attributes:


**listTeamRepos:**

Expected HTTP status: 200
*List team repos*


Attributes:


Returns array of GitHubRepo objects

**getTeamRepo:**

Expected HTTP status: 204
*Get team repo*


Attributes:


**removeTeamRepo:**

Expected HTTP status: 204
*Remove team repo*


Attributes:

## GitHubPulls
Could be access directly from GitHubClient->pulls

### Attributes:

 - GitHubPullsComments comments

### Sub-services:

 - GitHubPullsComments comments

### Methods:


**linkRelations:**

Expected HTTP status: 200
*Link Relations*


Attributes:

 - string parameterName (Optional) - `open` or `closed` to filter by state. Default
	 * 	is `open`.
 - string parameterName (Optional) - Filter pulls by head user and branch name in the format
	 * 	of: `user:ref-name`. Example: `github:new-script-format`.
 - string parameterName (Optional) - Filter pulls by base branch name. Example:
	 * 	`gh-pages`.

Returns array of GitHubPull objects

**getSinglePullRequest:**

Expected HTTP status: 200
*Get a single pull request*


Attributes:


Returns GitHubFullPull object

**mergability:**

Expected HTTP status: 200
*Mergability*


Attributes:

 - string parameterName (Optional) - State of this Pull Request. Valid values are
	 * 	`open` and `closed`.

Returns GitHubPull object

**listCommitsOnPullRequest:**

Expected HTTP status: 200
*List commits on a pull request*


Attributes:


Returns array of GitHubCommit objects

**listPullRequestsFiles:**

Expected HTTP status: 200
*List pull requests files*


Attributes:


Returns array of GitHubFile objects
## GitHubPullsComments
Could be access directly from GitHubClient->Pulls->pullsComments

### Attributes:


### Methods:


**listCommentsOnPullRequest:**

Expected HTTP status: 200
*List comments on a pull request*


Attributes:


Returns array of GitHubPullComment objects

**listCommentsInRepository:**

Expected HTTP status: 200
*List comments in a repository*


Attributes:

 - String parameterName (Optional) `created` or `updated`
 - String parameterName (Optional) `asc` or `desc`. Ignored without `sort` parameter.
 - String parameterName (Optional) of a timestamp in ISO 8601 format: YYYY-MM-DDTHH:MM:SSZ

Returns array of GitHubPullComment objects

**getSingleComment:**

Expected HTTP status: 200
*Get a single comment*


Attributes:


Returns GitHubPullComment object

**createComment:**

Expected HTTP status: 204
*Create a comment*


Attributes:

## GitHubRepos
Could be access directly from GitHubClient->repos

### Attributes:

 - GitHubReposCollaborators collaborators
 - GitHubReposComments comments
 - GitHubReposCommits commits
 - GitHubReposContents contents
 - GitHubReposDownloads downloads
 - GitHubReposForks forks
 - GitHubReposHooks hooks
 - GitHubReposKeys keys
 - GitHubReposMerging merging
 - GitHubReposStatistics statistics
 - GitHubReposStatuses statuses

### Sub-services:

 - GitHubReposCollaborators collaborators
 - GitHubReposComments comments
 - GitHubReposCommits commits
 - GitHubReposContents contents
 - GitHubReposDownloads downloads
 - GitHubReposForks forks
 - GitHubReposHooks hooks
 - GitHubReposKeys keys
 - GitHubReposMerging merging
 - GitHubReposStatistics statistics
 - GitHubReposStatuses statuses

### Methods:


**listYourRepositories:**

Expected HTTP status: 200
*List your repositories*


Attributes:


Returns array of GitHubSimpleRepo objects

**create:**

Expected HTTP status: 200
*Create*


Attributes:

 - boolean parameterName (Optional) - `true` makes the repository private, and
	 * 	`false` makes it public.
 - boolean parameterName (Optional) - `true` to enable issues for this repository,
	 * 	`false` to disable them. Default is `true`.
 - boolean parameterName (Optional) - `true` to enable the wiki for this
	 * 	repository, `false` to disable it. Default is `true`.
 - boolean parameterName (Optional) - `true` to enable downloads for this
	 * 	repository, `false` to disable them. Default is `true`.
 - String parameterName (Optional) - Update the default branch for this repository.

Returns GitHubFullRepo object

**listContributors:**

Expected HTTP status: 200
*List contributors*


Attributes:


Returns array of GitHubContributor objects

**listLanguages:**

Expected HTTP status: 200
*List languages*


Attributes:


Returns array of GitHubTeam objects

**listTags:**

Expected HTTP status: 200
*List Tags*


Attributes:


Returns array of GitHubTag objects

**listBranches:**

Expected HTTP status: 200
*List Branches*


Attributes:


Returns array of GitHubBranches objects

**getBranch:**

Expected HTTP status: 200
*Get Branch*


Attributes:


Returns array of GitHubBranch objects

**deleteRepository:**

Expected HTTP status: 204
*Delete a Repository*


Attributes:

## GitHubReposCollaborators
Could be access directly from GitHubClient->Repos->reposCollaborators

### Attributes:


### Methods:


**listReposCollaborators:**

Expected HTTP status: 200
*List*


Attributes:


Returns array of GitHubUser objects

**get:**

Expected HTTP status: 204
*Get*


Attributes:

## GitHubReposComments
Could be access directly from GitHubClient->Repos->reposComments

### Attributes:


### Methods:


**listCommitCommentsForRepository:**

Expected HTTP status: 200
*List commit comments for a repository*


Attributes:


Returns array of GitHubCommitComment objects

**listCommentsForSingleCommit:**

Expected HTTP status: 200
*List comments for a single commit*


Attributes:


Returns array of GitHubCommitComment objects

**createCommitComment:**

Expected HTTP status: 200
*Create a commit comment*


Attributes:


Returns GitHubCommitComment object

**updateCommitComment:**

Expected HTTP status: 204
*Update a commit comment*


Attributes:

## GitHubReposCommits
Could be access directly from GitHubClient->Repos->reposCommits

### Attributes:


### Methods:


**listCommitsOnRepository:**

Expected HTTP status: 200
*List commits on a repository*


Attributes:

 - string parameterName (Optional) - Sha or branch to start listing commits from.
 - string parameterName (Optional) - Only commits containing this file path
	 * 	will be returned.
 - string parameterName (Optional) - GitHub login, name, or email by which to filter by
	 * 	commit author
 - ISO 8601 Date parameterName (Optional) - Only commits after this date will be returned
 - ISO 8601 Date parameterName (Optional) - Only commits before this date will be returned

Returns array of GitHubCommit objects

**getSingleCommit:**

Expected HTTP status: 200
*Get a single commit*


Attributes:


Returns array of GitHubFullCommit objects

**compareTwoCommits:**

Expected HTTP status: 200
*Compare two commits*


Attributes:


Returns GitHubCommitComparison object
## GitHubReposContents
Could be access directly from GitHubClient->Repos->reposContents

### Attributes:


### Methods:


**getTheReadme:**

Expected HTTP status: 200
*Get the README*


Attributes:

 - string parameterName (Optional) - The String name of the Commit/Branch/Tag.  Defaults to `master`.

Returns GitHubReadmeContent object
## GitHubReposDownloads
Could be access directly from GitHubClient->Repos->reposDownloads

### Attributes:


### Methods:


**listDownloadsForRepository:**

Expected HTTP status: 200
*List downloads for a repository*


Attributes:


Returns array of GitHubDownload objects

**getSingleDownload:**

Expected HTTP status: 200
*Get a single download*


Attributes:


Returns GitHubDownload object
## GitHubReposForks
Could be access directly from GitHubClient->Repos->reposForks

### Attributes:


### Methods:


**listForks:**

Expected HTTP status: 200
*List forks*


Attributes:


Returns array of GitHubRepo objects
## GitHubReposHooks
Could be access directly from GitHubClient->Repos->reposHooks

### Attributes:


### Methods:


**listReposHooks:**

Expected HTTP status: 200
*List*


Attributes:


Returns GitHubHook object

**createHook:**

Expected HTTP status: 204
*Create a hook*


Attributes:

## GitHubReposKeys
Could be access directly from GitHubClient->Repos->reposKeys

### Attributes:


### Methods:


**listReposKeys:**

Expected HTTP status: 200
*List*


Attributes:


Returns array of GitHubPublicKey objects

**get:**

Expected HTTP status: 200
*Get*


Attributes:


Returns GitHubPublicKey object

**create:**

Expected HTTP status: 200
*Create*


Attributes:


Returns GitHubPublicKey object
## GitHubReposMerging
Could be access directly from GitHubClient->Repos->reposMerging

### Attributes:


### Methods:

## GitHubReposStatistics
Could be access directly from GitHubClient->Repos->reposStatistics

### Attributes:


### Methods:


**aWordAboutCaching:**

Expected HTTP status: 200
*A word about caching*


Attributes:


Returns array of GitHubRepoStatsContributors objects

**getTheLastYearOfCommitActivityData:**

Expected HTTP status: 200
*Get the last year of commit activity data*


Attributes:


Returns array of GitHubRepoStatsCommitActivity objects
## GitHubReposStatuses
Could be access directly from GitHubClient->Repos->reposStatuses

### Attributes:


### Methods:


**listStatusesForSpecificRef:**

Expected HTTP status: 200
*List Statuses for a specific Ref*


Attributes:

 - string parameterName (Required) - Ref to list the statuses from. It can be a SHA, a branch name, or a tag name.

Returns array of GitHubStatus objects
## GitHubSearch
Could be access directly from GitHubClient->search

### Attributes:


### Methods:

## GitHubUsers
Could be access directly from GitHubClient->users

### Attributes:

 - GitHubUsersEmails emails
 - GitHubUsersFollowers followers
 - GitHubUsersKeys keys

### Sub-services:

 - GitHubUsersEmails emails
 - GitHubUsersFollowers followers
 - GitHubUsersKeys keys

### Methods:


**getSingleUser:**

Expected HTTP status: 200
*Get a single user*


Attributes:


Returns GitHubFullUser object

**getTheAuthenticatedUser:**

Expected HTTP status: 200
*Get the authenticated user*


Attributes:


Returns GitHubPrivateUser object

**updateTheAuthenticatedUser:**

Expected HTTP status: 200
*Update the authenticated user*


Attributes:

 - string parameterName (Optional) - Publicly visible email address.

Returns GitHubPrivateUser object

**getAllUsers:**

Expected HTTP status: 200
*Get all users*


Attributes:


Returns array of GitHubUser objects
## GitHubUsersEmails
Could be access directly from GitHubClient->Users->usersEmails

### Attributes:


### Methods:


**listEmailAddressesForUser:**

Expected HTTP status: 204
*List email addresses for a user*


Attributes:

## GitHubUsersFollowers
Could be access directly from GitHubClient->Users->usersFollowers

### Attributes:


### Methods:


**listFollowersOfUser:**

Expected HTTP status: 204
*List followers of a user*


Attributes:

## GitHubUsersKeys
Could be access directly from GitHubClient->Users->usersKeys

### Attributes:


### Methods:


**listPublicKeysForUser:**

Expected HTTP status: 200
*List public keys for a user*


Attributes:


Returns array of GitHubSimplePublicKey objects

**listYourPublicKeys:**

Expected HTTP status: 200
*List your public keys*


Attributes:


Returns array of GitHubPublicKey objects

**getSinglePublicKey:**

Expected HTTP status: 200
*Get a single public key*


Attributes:


Returns GitHubPublicKey object

**createPublicKey:**

Expected HTTP status: 200
*Create a public key*


Attributes:


Returns GitHubPublicKey object