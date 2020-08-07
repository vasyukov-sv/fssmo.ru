import { InMemoryCache } from 'apollo-cache-inmemory'
import { BatchHttpLink } from 'apollo-link-batch-http'
import { ApolloClient } from 'apollo-client'
import 'isomorphic-fetch'
import { getHost } from '~/utils/helpers'

export default function (context, inject)
{
	const cache = new InMemoryCache({
		addTypename: false
	})

	if (!process.server)
		cache.restore(window.__NUXT__ ? window.__NUXT__.apollo.defaultClient : null)

	let host

	if (!context.env.baseUrl)
	{
		if (process.server)
		{
			const isHTTPS = require('is-https')
			host = 'http'+(isHTTPS(context.req, true) ? 's' : '')+'://'+context.req.headers.host
		}
		else
			host = getHost()
	}
	else
		host = context.env.baseUrl

	let headers = {}

	if (context.req)
	{
		let ip = context.req.headers['x-forwarded-for'] || context.req.connection.remoteAddress

		headers['cookie'] = context.req.headers.cookie
		headers['x-forwarded-for'] = ip
	}

	let link = new BatchHttpLink({
		uri: host+'/api/',
		credentials: 'include',
		headers: headers,
	})

	const apolloClient = new ApolloClient({
		link: link,
		cache: cache,
		ssrMode: !!process.server,
		ssrForceFetchDelay: 100,
		connectToDevTools: false,
		defaultOptions: {
			watchQuery: {
				errorPolicy: 'all',
			},
			query: {
				errorPolicy: 'all',
				fetchPolicy: 'network-only'
			},
			mutate: {
				errorPolicy: 'all'
			}
		}
	})

	inject('apollo', apolloClient)

	if (process.server)
	{
		context.beforeNuxtRender(async ({ nuxtState }) => {
			nuxtState.apollo = apolloClient.cache.extract()
		})
	}
}