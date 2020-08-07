<template>
	<div class="page-content">
		<HeaderContent></HeaderContent>
		<div class="personal-page-container" sticky-container>
			<section class="container">
				<div class="row">
					<div class="col-md-4 order-md-1">
						<div class="personal-menu">
							<div v-sticky>
								<Menu/>
							</div>
						</div>
					</div>
					<div class="col-md-8">

							<table>
								<thead>
								<tr>
									<th>Дисциплина</th>
									<th>Категория</th>
									<th>Место</th>
									<th>Баллы</th>
								</tr>
								</thead>
								<tbody>
									<tr v-for="item in items">
										<td>{{ item['discipline'] }}</td>
										<td>{{ item['group'] }}</td>
										<td>{{ item['place'] }}</td>
										<td>{{ (item['rating']).toFixed(2) }}</td>
									</tr>
								</tbody>
							</table>

					</div>
				</div>
			</section>
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
	import Menu from '~/components/personal/menu.vue'
	import gql from 'graphql-tag';

	const ratingsListQuery = gql`query {
		items: currentUserRating
	}`;

	export default {
		name: "rating",
		middleware: 'authorization',
		components: {
			BottomResults,
			BottomImg,
			FutureEventsBottom,
			BottomRating,
			Menu,
		},
		async asyncData (context)
		{
			try
			{
				let ratingsRequest = context.app.$apollo.query({
					query: ratingsListQuery,
					fetchPolicy: 'cache-first',
				})

				const ratings = await Promise.all([
					ratingsRequest,
					context.store.dispatch('getPageInfo', context.route.path)
				])
				.then(([ratings]) =>
				{
					if (typeof ratings.errors !== 'undefined')
						throw new Error(ratings.errors[0].message)

					return ratings.data['items']
				})

				return {
					items: ratings || [],
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