import {
	serverInit,
	getPageData,
	disciplinesQuery,
	compititionsList,
	ratingsTypes,
	lastResultsList,
	winnersList,
	sliderList,
	sponsorsList,
} from '~/utils/query.gql'

export default {
	nuxtServerInit (store, context)
	{
		const headers = context.req && context.req.headers;

		if (headers.cookie === undefined)
			headers.cookie = '';

		const serverQuery = context.app.$apollo.query({
			query: serverInit,
			variables: {
				url: context.route.path
			},
		})

		return Promise.all([
			serverQuery,
			context.store.dispatch('getFutureEvents'),
			context.store.dispatch('getRatingsTypes'),
			context.store.dispatch('getLastResults'),
			context.store.dispatch('getSponsors'),
		])
		.then(([result]) =>
		{
			if (result.data)
			{
				if (typeof result.data.user !== 'undefined')
					store.commit('currentUser', result.data.user);

				if (typeof result.data.page !== 'undefined')
					store.commit('setArea', result.data.page['area']);
			}
		})
	},
	userLogout ({ commit }) {
		commit('currentUser', null);
	},
	getPageInfo ({ commit }, url)
	{
		if (typeof url === 'undefined')
			url = this.app.context.route.path

		return this.app.$apollo.query({
			query: getPageData,
			variables: {
				url: url
			},
			fetchPolicy: 'cache-first',
		})
		.then((result) =>
		{
			if (result.data && typeof result.data.page !== 'undefined')
				commit('setPage', result.data['page']);

			return result;
		})
	},
	getFutureEvents ({ commit })
	{
		return Promise.all([
			this.app.$apollo.query({
				query: disciplinesQuery,
				fetchPolicy: 'cache-first',
			}),
			this.app.$apollo.query({
				query: compititionsList,
				variables: {
					page: 1,
					limit: 6,
					filter: {},
				},
				fetchPolicy: 'cache-first',
			})
		])
		.then(([disciplines, competitions]) =>
		{
			commit('setFutureEvents', {
				disciplines: disciplines.data.disciplines,
				competitions: competitions.data.competitions
			});

			return [disciplines, competitions]
		})
	},
	getFutureEventsByDisciplineId ({ commit }, disciplineId)
	{
		return this.app.$apollo.query({
			query: compititionsList,
			variables: {
				page: 1,
				limit: 6,
				filter: {
					discipline: disciplineId
				},
			},
			fetchPolicy: 'cache-first',
		})
		.then((result) =>
		{
			commit('setFutureEvents', {
				competitions: result.data.competitions
			});

			return result;
		})
	},
	getRatingsTypes ({ commit })
	{
		return this.app.$apollo.query({
			query: ratingsTypes,
			fetchPolicy: 'cache-first',
		})
		.then((result) =>
		{
			commit('setRatingTypes', result.data['ratingsTypes']);

			return result;
		})
	},
	getLastResults ({ commit })
	{
		return this.app.$apollo.query({
			query: lastResultsList,
			variables: {
				page: 1,
				limit: 8,
			},
			fetchPolicy: 'cache-first'
		})
		.then((result) =>
		{
			commit('setLastResults', result.data['competitionsResults']);

			return result.data['competitionsResults'];
		})
	},
	getWinners ({ commit })
	{
		return this.app.$apollo.query({
			query: winnersList,
			variables: {
				page: 1,
				limit: 8,
			},
			fetchPolicy: 'cache-first'
		})
		.then((result) =>
		{
			commit('setWinners', result.data['winners']);

			return result;
		})
	},
	getSlider ({ commit })
	{
		return this.app.$apollo.query({
			query: sliderList,
			variables: {
				page: 1,
				limit: 8,
			},
			fetchPolicy: 'cache-first'
		})
		.then((result) =>
		{
			commit('setSlider', result.data['slider']);

			return result;
		})
	},
	getSponsors ({ commit })
	{
		return this.app.$apollo.query({
			query: sponsorsList,
			variables: {
				page: 1,
				limit: 8,
			},
			fetchPolicy: 'cache-first'
		})
		.then((result) =>
		{
			commit('setSponsors', result.data['sponsors']);

			return result;
		})
	},
};