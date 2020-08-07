<template>
	<div class="page-content page-dark">
		<HeaderContent class="page-content-header-dark" />

		<div class="page-content-dark-container">
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
							<transition-group name="fade" tag="div">
								<div v-for="item in events" :key="item.id" class="result-col">
									<ResultItem :item="item"></ResultItem>
								</div>
							</transition-group>
						</div>

						<div v-if="allowLoadMore" class="loadmore-btn">
							<a href="#" @click.prevent="loadMore" class="btn btn-white-border">показать еще</a>
						</div>
					</div>
				</div>
			</section>
		</div>

		<section class="blue-img-section">
			<div class="container">
				<BottomRating></BottomRating>
			</div>
		</section>

		<FutureEventsBottom></FutureEventsBottom>
		<BottomImg></BottomImg>
	</div>
</template>

<script>
	import BottomRating from '~/components/ratingBottom.vue'
	import BottomImg from '~/components/bottomImg.vue'
	import FutureEventsBottom from '~/components/futureEventsBottom.vue';
	import SidebarFilter from '~/components/competitions/sidebarFilter.vue'
	import ResultItem from '~/components/results/resultItem.vue'

	import gql from 'graphql-tag';
	import { convertMeta } from '~/plugins/helpers';

	const itemsListQuery = gql`query ($page: Int!, $limit: Int!, $filter: String!) {
		filter: competitionsResultsFilter (filter: $filter)
		items: competitionsResults (page: $page, limit: $limit) {
			id
      		title
      		url
      		location
      		discipline
      		members
      		date
      		groups
      		targets
      		winner
		}
	}`;

	const limit = 8;

	export default {
	  	components: {
		  	BottomImg,
			FutureEventsBottom,
			BottomRating,
			SidebarFilter,
			ResultItem,
	  	},
		async asyncData (context)
		{
			try
			{
				const itemsList = context.app.$apollo.query({
					query: itemsListQuery,
					variables: {
						page: 1,
						limit: limit,
						filter: context.route.path,
					}
				})

				const results = await Promise.all([
					itemsList,
					context.store.dispatch('getPageInfo')
				])
				.then(([results]) =>
				{
					if (typeof results.errors !== 'undefined')
						throw new Error(results.errors[0].message)

					return results.data
				})

				return {
					filter: results['filter']['items'],
					events: results['items'],
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
				filterOpened: false
		  	}
		},
		methods: {
			async loadMore ()
			{
				this.page++

				try
				{
					const items = await this.$apollo.query({
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

					this.allowLoadMore = !(items.length < limit)

					items.forEach((item) => {
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
					const result = await this.$apollo.query({
						query: itemsListQuery,
						variables: {
							page: this.page,
							limit: limit,
							filter: this.getFilterString()
						},
					})
					.then ((result) =>
					{
						if (typeof result.errors !== 'undefined')
							throw new Error(result.errors[0].message)

						return result.data
					})

					this.allowLoadMore = !(result['items'].length < limit)

					this.events = result['items']
					this.filter = result['filter']['items']

					history.replaceState(null, null, result['filter']['url'])
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