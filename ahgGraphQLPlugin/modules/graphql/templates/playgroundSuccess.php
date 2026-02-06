<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>GraphQL Playground - Archive</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/graphiql@3.0.10/graphiql.min.css" crossorigin="anonymous">
    <style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
        html, body, #graphiql {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        .graphiql-container {
            height: 100vh;
        }
    </style>
</head>
<body>
    <div id="graphiql"></div>

    <script src="https://cdn.jsdelivr.net/npm/react@18.2.0/umd/react.production.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/react-dom@18.2.0/umd/react-dom.production.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/graphiql@3.0.10/graphiql.min.js" crossorigin="anonymous"></script>

    <script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
        const fetcher = GraphiQL.createFetcher({
            url: '/api/graphql',
            headers: {
                'Content-Type': 'application/json',
            },
        });

        const defaultQuery = `# Welcome to the Archive GraphQL Playground
#
# Start exploring with these example queries:

# Get a single item by slug
query GetItem {
  item(slug: "example-item") {
    id
    slug
    title
    levelOfDescription {
      name
    }
    scopeAndContent
    dates {
      eventType
      dateDisplay
      startDate
      endDate
    }
    repository {
      name
    }
  }
}

# Browse items with pagination
query BrowseItems {
  items(first: 10) {
    totalCount
    edges {
      node {
        id
        slug
        title
        levelOfDescription {
          name
        }
        sector
      }
      cursor
    }
    pageInfo {
      hasNextPage
      endCursor
    }
  }
}

# Browse repositories
query BrowseRepositories {
  repositories(first: 10) {
    totalCount
    edges {
      node {
        id
        slug
        name
        itemCount
      }
    }
  }
}

# List all taxonomies
query ListTaxonomies {
  taxonomies {
    id
    name
    usage
  }
}

# Get current user
query Me {
  me {
    id
    username
  }
}
`;

        ReactDOM.render(
            React.createElement(GraphiQL, {
                fetcher: fetcher,
                defaultQuery: defaultQuery,
                defaultEditorToolsVisibility: true,
            }),
            document.getElementById('graphiql')
        );
    </script>
</body>
</html>
