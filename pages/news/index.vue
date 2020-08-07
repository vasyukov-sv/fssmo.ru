<template>
	<div class="page-content">
		<HeaderContent></HeaderContent>
		<section class="container">

			<nav class="page-list-nav">
				<ul>
					<li>
						<a href="#" @click.prevent="setActive(0)" :class="{ active: isActive(0) }">Все</a>
					</li>
					<li v-for="discipline in disciplines">
						<a href="#" @click.prevent="setActive(discipline['id'])" :class="{ active: isActive(discipline['id']) }">{{ discipline['title'] }}</a>
					</li>
				</ul>
			</nav>

			<div class="news-list">
				<transition-group name="fade" tag="div">
					<NewsItem v-for="item in news" :key="item['id']" :item="item"></NewsItem>
				</transition-group>
				<div v-if="allowLoadMore" class="loadmore-btn">
					<a href="#" @click.prevent="loadMore" class="btn btn-blue-border">показать еще</a>
				</div>
			</div>
		</section>

		<section class="blue-img-section">
			<div class="container">
				<BottomResults/>
				<BottomRating/>
			</div>
		</section>

		<FutureEventsBottom/>
		<BottomImg/>
	</div>
</template>

<script>
	import BottomResults from '~/components/resultsBottom.vue'
	import BottomRating from '~/components/ratingBottom.vue'
	import BottomImg from '~/components/bottomImg.vue'
	import FutureEventsBottom from '~/components/futureEventsBottom.vue'
	import NewsItem from '~/components/news/newsItem.vue'
	import gql from 'graphql-tag';

	const newsListQuery = gql`query ($discipline: Int!, $pagination: Objects!) {
		items: news (discipline: $discipline, pagination: $pagination) {
			id
			title
			url
			preview
			date
			image
			competition
			discipline
		}
	}`;

	const limit = 9;

	export default {
		name: "news",
		components: {
			NewsItem,
			BottomResults,
			BottomRating,
			BottomImg,
			FutureEventsBottom,
		},
		async asyncData (context)
		{
			try
			{
				let newsListRequest = context.app.$apollo.query({
					query: newsListQuery,
					variables: {
						discipline: 0,
						pagination: {
							limit: limit,
							page: 1,
						},
					},
					fetchPolicy: 'cache-first',
				})

				const news = await Promise.all([
					newsListRequest,
					context.store.dispatch('getPageInfo')
				])
				.then(([news]) =>
				{
					if (typeof news.errors !== 'undefined')
						throw new Error(news.errors[0].message);

					return news.data['items']
				})

				return {
					allowLoadMore: news.length >= limit,
					news: news,
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
				allowLoadMore: false,
				page: 1,
				discipline: 0,
			}
		},
		computed: {
			disciplines () {
				return this.$store.state.futureEvents.disciplines || []
			}
		},
		methods: {
			isActive (id) {
				return this.discipline === id;
			},
			setActive (id) {
				this.discipline = id
				this.page = 0
				this.updateEventsList()
			},
			updateEventsList () {
				this.loadMore()
			},
			async loadMore ()
			{
				this.page++

				try
				{
					const news = await this.$apollo.query({
						query: newsListQuery,
						variables: {
							discipline: this.discipline,
							pagination: {
								limit: limit,
								page: this.page,
							},
						},
					})
					.then((result) =>
					{
						if (typeof result.errors !== 'undefined')
							throw new Error(result.errors[0].message)

						return result.data['items']
					})

					this.allowLoadMore = !(news.length < limit)

					if (this.page === 1)
						this.news = news
					else
					{
						news.forEach((item) => {
							this.news.push(item)
						})
					}
				}
				catch (e)
				{
					this.$modal.alert({
						title: 'Ошибка',
						content: e.message
					})
				}
			},
		}
	}
</script>