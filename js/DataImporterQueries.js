import { ApolloClient, InMemoryCache, createHttpLink, gql } from '@apollo/client';

function getGraphQLClient(uri, options = null) {
  const httpLink = createHttpLink({
    uri: uri
  });
  const client = new ApolloClient({
    link: httpLink,
    cache: new InMemoryCache()
  });
  return client;
}

const getImporterList = (url, callback) => {
  const client = getGraphQLClient(url, {});
  client
    .query({
      query: gql `query { importerList { importers { id, code, name, type, dataFormats { name, code } } } }`
    })
    .then(function (result) {
      callback(result.data['importerList']);
    }).catch(function (error) {
      console.log("Error while attempting to fetch importer list: ", error);
    });
}

export { getGraphQLClient, getImporterList };