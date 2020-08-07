<template>
	<div class="page-content">
		<HeaderContent></HeaderContent>
		<section class="container">
			<div class="disciplines-container">
				<Discipline v-for="item in disciplines" :key="item['id']" :item="item"/>
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
	import Discipline from '~/components/disciplines/discipline.vue'
	import gql from 'graphql-tag';

	const disciplinesQuery = gql`query {
		disciplines: siteDisciplines
	}`;

	export default {
		name: 'disciplines',
		components: {
			BottomResults,
			BottomRating,
			BottomImg,
			FutureEventsBottom,
			Discipline,
		},
		async asyncData (context)
		{
			try
			{
				let disciplinesRequest = context.app.$apollo.query({
					query: disciplinesQuery,
				})

				const disciplines = await Promise.all([
					disciplinesRequest,
					context.store.dispatch('getPageInfo')
				])
				.then(([disciplines]) =>
				{
					if (typeof disciplines.errors !== 'undefined')
						throw new Error(disciplines.errors[0].message)

					return disciplines.data['disciplines']
				})

				return {
					disciplines: disciplines || [],
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