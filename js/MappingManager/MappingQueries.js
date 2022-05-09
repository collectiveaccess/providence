import { ApolloClient, InMemoryCache, createHttpLink, gql } from '@apollo/client';
import { setContext } from '@apollo/client/link/context';

function getGraphQLClient(uri, options = null) {
  const httpLink = createHttpLink({
    uri: uri
  });
  const authLink = setContext((_, { headers }) => {
    const token = providenceUIApps.MappingManager.key;
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

const getImportersList = (url, callback) => {
  const client = getGraphQLClient(url, {});
  client
    .query({
      query: gql
        `
          query {
              list {
                  id, name, code, table, formats, source
              }
          }
        `
    })
    .then(function (result) {
      callback(result.data['list']);
    }).catch(function (error) {
      console.log("Error while attempting to get importer List: ", error);
    });
}

const getImporterForm = (url, id, callback) => {
  const client = getGraphQLClient(url, {});
  client
    .query({
      query: gql
        `
          query($id: Int) {
            importerForm(id: $id) {
              title, required, properties, uiSchema, values
            }
          }
        `
      , variables: { "id": id }
    })
    .then(function (result) {
      callback(result.data['importerForm']);
    }).catch(function (error) {
      console.log("Error while attempting to get importerForm: ", error);
    });
}

const getNewImporterForm = (url, callback) => {
  const client = getGraphQLClient(url, {});
  client
    .query({
      query: gql
        `
          query {
            importerForm {
              title, required, properties, uiSchema, values
            }
          }
        `
    })
    .then(function (result) {
      callback(result.data['importerForm']);
    }).catch(function (error) {
      console.log("Error while attempting to get importerForm: ", error);
    });
}

const getListMappings = (url, id, callback) => {
  const client = getGraphQLClient(url, {});
  client
    .query({
      query: gql
        `
          query ($id: Int) {
            listMappings(id: $id) {
              mappings { id, type, group_id, source, destination, options { name, value},
                refineries { refinery, options { name, value }}
                replacement_values { original, replacement}
                }
            }
          }
        `, variables: { "id": id }
    })
    .then(function (result) {
      callback(result.data['listMappings']);
    }).catch(function (error) {
      console.log("Error while attempting to get importerForm: ", error);
    });
}

function addImporter(uri, name, formats, code, table, type, settings, callback) {
  const client = getGraphQLClient(uri, {});
  client
    .mutate({
      mutation: gql
        `
          mutation (
            $name: String, 
            $formats: [String], 
            $code: String, 
            $table: String, 
            $type: Int, 
            $settings: [ImporterSetting]
          ) 
          { 
            add (
              name: $name, 
              formats: $formats, 
              code: $code, 
              table: $table, 
              type: $type, 
              settings: $settings
            ) 
            {
              id, name, code, table, type, formats, source, errors
            } 
          }
        `
      , variables: { "name": name, "formats": formats, "code": code, "table": table, "type": type, "settings": settings }
    })
    .then(function (result) {
		console.log("add", result);
      callback(result.data['add']);
    }).catch(function (error) {
      console.log("Error while attempting to add importer: ", error);
    });
}

function editMappings(uri, id, mappings, callback) {
  const client = getGraphQLClient(uri, {});
  client
    .mutate({
      mutation: gql
        `
          mutation (
            $id: Int, 
            $mappings: [ImporterMappingInput], 
          ) 
          { 
            editMappings (
              id: $id, 
              mappings: $mappings, 
            ) 
            {
              id, errors {id, code, message}, warnings {id, code, message}, info {id, code, message}
            } 
          }
        `
      , variables: { "id": id, "mappings": mappings }
    })
    .then(function (result) {
      console.log("editMappings", result);
      callback(result.data['editMappings']);
    }).catch(function (error) {
      console.log("Error while attempting to edit mappings: ", error);
    });
}

function deleteImporter(uri, id, callback) {
  const client = getGraphQLClient(uri, {});
  client
    .mutate({
      mutation: gql
        `
          mutation ($id: Int) {
            delete(id: $id) {
                id, name, code, table, type, formats, source, errors
            }
          }
        `
      , variables: { 'id': id }
    })
    .then(function (result) {
      callback(result.data['delete']);
    }).catch(function (error) {
      console.log("Error while attempting to delete Importer: ", error);
    });
}

function deleteMapping(uri, id, mapping_id, callback) {
  const client = getGraphQLClient(uri, {});
  client
    .mutate({
      mutation: gql
        `
          mutation ($id: Int, $mapping_id: Int) {
            deleteMapping(id: $id, mapping_id: $mapping_id) {
                id, errors { message }
            }
          }
        `
      , variables: { 'id': id, 'mapping_id': mapping_id }
    })
    .then(function (result) {
      callback(result.data['deleteMapping']);
    }).catch(function (error) {
      console.log("Error while attempting to delete mapping: ", error);
    });
}

function editImporter(uri, id, name, formats, code, table, type, settings, callback) {
  const client = getGraphQLClient(uri, {});
  client
    .mutate({
      mutation: gql
        `
          mutation ($id: Int, $name: String, $formats: [String], $code: String, $table: String, $type: Int, $settings: [ImporterSetting]) { 
            edit(id: $id, name: $name, formats: $formats, code: $code, table: $table, type: $type, settings: $settings) {
              id, name, code, table, type, formats, source, errors
            } 
          }
        `
      , variables: { 'id': id, 'table': table, "name": name, "formats": formats, "code": code, "type": type, "settings": settings }
    })
    .then(function (result) {
      callback(result.data['edit']);
    }).catch(function (error) {
      console.log("Error while attempting to edit Importer: ", error);
    });
}

export { getGraphQLClient, getImportersList, addImporter, deleteImporter, deleteMapping, editImporter, editMappings, getImporterForm, getNewImporterForm, getListMappings };