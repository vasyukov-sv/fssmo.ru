<template>
	<div class="page-content">
		<HeaderContent/>
		<section class="container">
			<div class="judges-list competition-winners">
				<div class="row">
					<div v-for="item in judges" class="competition-winners-col judges-col">
						<div class="competition-winners-panel judges-panel">
							<div class="competition-winner-img" :style="{backgroundImage: 'url('+item['image']+')'}">
								<img :src="item['image']" :alt="item['title']" />
							</div>
							<div class="competition-winners-panel-info">
								<div class="judge-name">{{ item['title'] }}</div>
								<div v-if="item['position']" class="judge-post">{{ item['position'] }}</div>
								<div v-if="item['text']" class="judge-text" v-html="item['text']"></div>
							</div>
						</div>
					</div>
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
	import gql from 'graphql-tag';

	const judgesQuery = gql`query {
		judges {
			id
			title
			text
			image
			position
		}
	}`;

	export default {
		name: 'judges',
		components: {
			BottomResults,
			BottomRating,
			BottomImg,
			FutureEventsBottom,
		},
		async asyncData (context)
		{
			try
			{
				let judgesRequest = context.app.$apollo.query({
					query: judgesQuery,
				})

				let judges = await Promise.all([
					judgesRequest,
					context.store.dispatch('getPageInfo')
				])
				.then(([data]) =>
				{
					if (typeof data.errors !== 'undefined')
						throw new Error(data.errors[0].message)

					return data.data['judges']
				})

				return {
					judges: judges || []
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
	}
</script>