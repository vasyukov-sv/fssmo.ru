<template>
	<div class="page-content">
		<HeaderContent class="news-detail-header" :newsDate="item['date']">
			<template v-slot:beforeTitle>
				<div class="news-detail-page-date">{{ item['date'] | date('DD MMMM YYYY') }}</div>
				{{ item['discipline'] }}
			</template>
		</HeaderContent>
		<section class="container">
			<div class="news-detail-top">
				<div class="news-detail-text" v-html="item['text']"></div>

				<div v-if="item['competition']">
					<template v-if="item['competition']['url'].indexOf('http') < 0">
						<nuxt-link v-if="item['competition']['registration']" :to="item['competition']['url']+'registration/'" class="btn btn-gold">зарегистрироваться</nuxt-link>
						<nuxt-link :to="item['competition']['url']" class="btn btn-gold">о соревновании</nuxt-link>
					</template>
					<a v-else :href="item['competition']['url']" class="btn btn-gold">зарегистрироваться</a>
				</div>

				<social-sharing :title="item['title']" inline-template>
					<div class="share-container">
						<div class="share-container-title">поделиться</div>
						<div class="socials-login-list">
							<network network="facebook" class="sharing-icon fb-icon"></network>
							<network network="vk" class="sharing-icon vk-icon"></network>
							<network network="odnoklassniki" class="sharing-icon od-icon"></network>
						</div>
					</div>
				</social-sharing>
			</div>
		</section>

		<div class="news-direction">
			<div class="container">
				<div class="news-direction-btns">
					<div class="news-direction-btn-wrap">
						<nuxt-link v-if="item['arrows']['prev']" :to="item['arrows']['prev']" class="news-direction-btn news-prev">Предыдущая новость<span class="news-arr"></span></nuxt-link>
					</div>
					<div class="news-direction-btn-wrap">
						<nuxt-link v-if="item['arrows']['next']" :to="item['arrows']['next']" class="news-direction-btn news-next">Следующая новость<span class="news-arr"></span></nuxt-link>
					</div>
				</div>
			</div>
		</div>

		<div class="additional-news">
			<div class="container">
				<h3>Еще новости по теме</h3>
				<div class="news-list">
					<NewsItem v-for="(itm, i) in item['similar']" :key="i" :item="itm"></NewsItem>
				</div>
				<div class="loadmore-btn">
					<nuxt-link to="/news/" class="btn btn-blue-border">Все новости</nuxt-link>
				</div>
			</div>
		</div>

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
	import SocialSharing from 'vue-social-sharing';

	import gql from 'graphql-tag';

	const newsDetailQuery = gql`query ($id: String!) {
		detail: newsDetail (id: $id) {
			title
			url
			text
			date
			arrows
			discipline
			competition
			similar {
				title
				url
				preview
				date
			}
		}
	}`;

	export default {
		name: "news-detail",
		components: {
			NewsItem,
			SocialSharing,
			BottomResults,
			BottomRating,
			BottomImg,
			FutureEventsBottom,
		},
		async asyncData (context)
		{
			try
			{
				let newsDetailRequest = context.app.$apollo.query({
					query: newsDetailQuery,
					variables: {
						id: context.route.params.id,
					},
					fetchPolicy: 'cache-first',
				})

				const item = await Promise.all([
					newsDetailRequest,
					context.store.dispatch('getPageInfo')
				])
				.then(([result]) =>
				{
					if (typeof result.errors !== 'undefined')
						throw new Error(result.errors[0].message)

					return result.data['detail']
				})

				return {
					item: item,
				}
			}
			catch (e)
			{
				return context.error({
					statusCode: 404,
					message: e.message,
				})
			}
		},
	}
</script>