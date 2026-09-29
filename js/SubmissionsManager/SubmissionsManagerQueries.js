import { ApolloClient, InMemoryCache, createHttpLink, gql } from '@apollo/client';
import { setContext } from '@apollo/client/link/context';

function getGraphQLClient(uri, options = null) {
  const httpLink = createHttpLink({
    uri: uri
  });
  const authLink = setContext((_, { headers }) => {
    const token = providenceUIApps.SubmissionsManager.key;
    return {
      headers: {
        ...headers,
        authorization: token ? `Bearer ${token}` : "",
      }
    }
  });
  const client = new ApolloClient({
    link: authLink.concat(httpLink),
    cache: new InMemoryCache()
  });
  return client;
}

/* Returns the list of sessions, both in_progress and submitted  */
const getSessionList = (url, filterData, callback) => {
  const client = getGraphQLClient(url + '/Submission', {});
  client
    .query({
      query: gql`
        query($date: String, $user_id: Int, $status: String) { sessionList(date: $date, status: $status, user_id: $user_id) { sessions { label, sessionKey, user_id, user, username, email, status, statusDisplay, createdOn, lastActivityOn, source, files, filesImported, totalSize, totalBytes, receivedSize, receivedBytes, errors { filename, message}, warnings { filename, message }, searchUrl }}}`, variables: { 'date': filterData.date, 'user_id': filterData.user_id, 'status': filterData.status }
    })
    .then(function (result) {
      // console.log('sessionList result: ', result.data.sessionList.sessions);
      callback(result.data['sessionList']);
    }).catch(function (error) {
      console.log("Error while attempting to fetch session list: ", error);
    });
}

const getSession = (url, sessionKey, callback) => {
  const client = getGraphQLClient(url + '/Submission', {});
  client
    .query({
      query: gql`
        query($sessionKey: String) { getSession (sessionKey: $sessionKey) { sessionKey, user_id, user, username, email, formData, files, filesImported, totalSize, label, formInfo, filesUploaded { name, path, complete, totalSize, receivedSize, totalBytes, receivedBytes }, errors { filename, message }, warnings { filename, message }, urls { filename, url }, searchUrl }}`, variables: { 'sessionKey': sessionKey }
    })
    .then(function (result) {
      // console.log('getSession result: ', result.data);
      callback(result.data['getSession']);
    }).catch(function (error) {
      console.log("Error while attempting to get session ", error);
    });
}

const updateSessionStatus = (url, sessKey, status, callback) => {
  const client = getGraphQLClient(url + '/Submission', {});
  console.log("send status", status);
  client
    .mutate({
      mutation: gql`
        mutation($sessionKey: String, $status: String) { updateSessionStatus (sessionKey: $sessionKey, status: $status) { updated, validationErrors }}`, variables: { 'sessionKey': sessKey, 'status': status }
    })
    .then(function (result) {
      callback(result.data['updateSessionStatus']);
    }).catch(function (error) {
      console.log("Error while attempting to update session status ", error);
    });
}

const getSessionFilterValues = (url, callback) => {
  const client = getGraphQLClient(url + '/Submission', {});
  client
    .query({
      query: gql`
        query { getSessionFilterValues { users { user_id, fname, lname, email, user_name }, statuses }}`
    })
    .then(function (result) {
      callback(result.data['getSessionFilterValues']);
    }).catch(function (error) {
      console.log("Error while attempting to fetch filter values: ", error);
    });
}


export { getGraphQLClient, getSessionList, getSession, updateSessionStatus, getSessionFilterValues } ;
