<template>
	<div class="page-content">
		<HeaderContent />
		<section class="container">
			<div class="competition-results">
				<button class="competition-sidebar-toggle" @click="filterOpened = !filterOpened">
					<span v-if="!filterOpened">Показать</span><span v-else>Скрыть</span> фильтр</button>
				<div class="competition-results-sidebar" :class="{ 'active': filterOpened }">
					<div class="competition-results-sidebar-content" v-sticky>
						<SidebarFilter :items="filter" @update="updateItems"></SidebarFilter>
					</div>
				</div>
				<div class="competition-results-list">
					<div class="competition-results-list-row">
						<div>
							<div v-for="item in events" :key="item.id" class="result-col">
								<EventItem :item="item"></EventItem>
							</div>
						</div>
					</div>

					<div v-if="allowLoadMore" class="loadmore-btn">
						<a href="#" @click.prevent="loadMore" class="btn btn-blue-border">показать еще</a>
					</div>
				</div>
			</div>
		</section>

		<section class="blue-img-section">
			<div class="container">
				<bottomResults></bottomResults>
				<bottomRating></bottomRating>
			</div>
		</section>

		<BottomImg/>
	</div>
</template>

<script>
	import BottomResults from '~/components/resultsBottom.vue'
	import BottomRating from '~/components/ratingBottom.vue'
	import BottomImg from '~/components/bottomImg.vue'
	import EventItem from '~/components/competitions/eventItem.vue'
	import SidebarFilter from '~/components/competitions/sidebarFilter.vue'

	import gql from 'graphql-tag';

	const itemsListQuery = gql`query ($page: Int!, $limit: Int!, $filter: String!) {
		filter: competitionsFilter (filter: $filter)
		items: competitions (page: $page, limit: $limit) {
			id
			title
			url
			discipline
			location
			date_from
			date_to
			image
			registration
		}
	}`;

	const limit = 6;

	export default {
		components: {
			BottomRating,
			BottomResults,
			BottomImg,
			EventItem,
			SidebarFilter,
		},
		async asyncData (context)
		{
			try
			{
				let itemsListRequest = context.app.$apollo.query({
					query: itemsListQuery,
					variables: {
						page: 1,
						limit: limit,
						filter: context.route.path,
					},
					fetchPolicy: 'cache-first',
				})

				const result = await Promise.all([
					itemsListRequest,
					context.store.dispatch('getPageInfo')
				])
				.then(([result]) =>
				{
					if (typeof result.errors !== 'undefined')
						throw new Error(result.errors[0].message)

					return result.data
				})

				return {
					allowLoadMore: result['items'].length >= limit,
					filter: result['filter']['items'],
					events: result['items'],
				}
			}
			catch (e)
			{
				return context.error({
					statusCode: 500,
					message: e.message,
				})
			}
		},
		data () {
			return {
				page: 1,
				allowLoadMore: true,
				events: [],
				filterOpened: false
			}
		},
		methods: {
			async loadMore ()
			{
				this.page++

				try
				{
					const events = await this.$apollo.query({
						query: itemsListQuery,
						variables: {
							page: this.page,
							limit: limit,
							filter: this.getFilterString(),
						},
					})
					.then((result) =>
					{
						if (typeof result.errors !== 'undefined')
							throw new Error(result.errors[0].message)

						return result.data['items']
					})

					this.allowLoadMore = !(events.length < limit)

					events.forEach((item) => {
						this.events.push(item)
					})
				}
				catch (e)
				{
					this.$modal.alert({
						title: 'Ошибка',
						content: e.message
					})
				}
			},
			getFilterString ()
			{
				let filter = {};

				this.filter.forEach((item) =>
				{
					item['values'].forEach((value) =>
					{
						if (value['checked'])
							filter[value['id']] = 'Y';
					});
				});

				return Object.keys(filter).map(k => encodeURIComponent(k)+'='+encodeURIComponent(filter[k])).join('&');
			},
			async updateItems ()
			{
				this.page = 1

				try
				{
					const events = await this.$apollo.query({
						query: itemsListQuery,
						variables: {
							page: this.page,
							limit: limit,
							filter: this.getFilterString()
						},
					})
					.then((result) =>
					{
						if (typeof result.errors !== 'undefined')
							throw new Error(result.errors[0].message)

						return result.data
					})

					this.allowLoadMore = !(events['items'].length < limit)

					this.events = events['items']
					this.filter = events['filter']['items']

					history.replaceState(null, null, events['filter']['url'])
				}
				catch (e)
				{
					this.$modal.alert({
						title: 'Ошибка',
						content: e.message
					})
				}
			}
		}
	}
</script>