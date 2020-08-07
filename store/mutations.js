export default {
	currentUser (state, user)
	{
		state.user = user

		if (user && this.app.$sentry)
		{
			this.app.$sentry.configureScope((scope) =>
			{
				scope.setUser({
					'id': user['id'],
				})
			})
		}
	},
	setPage (state, page)
	{
		state.page = page;
	},
	setFutureEvents (state, payload)
	{
		if (state.futureEvents)
			Object.assign(state.futureEvents, payload);
		else
			state.futureEvents = payload;
	},
	setRatingTypes (state, payload) {
		state.ratingsTypes = payload;
	},
	setLastResults (state, payload) {
		state.lastResults = payload;
	},
	setWinners (state, payload) {
		state.winners = payload;
	},
	setSlider (state, payload) {
		state.slider = payload;
	},
	setSponsors (state, payload) {
		state.sponsors = payload;
	},
	setArea (state, payload) {
		state.area = payload;
	},
};