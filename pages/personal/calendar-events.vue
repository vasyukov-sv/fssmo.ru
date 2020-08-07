<template>
	<div class="page-content">
		<HeaderContent/>
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
						<div class="default-table">
							<table>
								<thead>
									<tr>
										<th>Даты проведения</th>
										<th>Название</th>
										<th>Дисциплина</th>
										<th>Страна</th>
										<th>Город</th>
										<th>Контакты</th>
									</tr>
								</thead>
								<tbody>
									<template v-for="(item, i) in _items">
										<tr v-if="i === 0 || _items[i - 1]['month'] !== item['month']" class="person-name-row">
											<td colspan="10"><span class="table-person-name">{{ item['active_from'] | date("MMMM YYYY") }}</span></td>
										</tr>
										<tr>
											<td>
												<div class="dates">
													<template v-if="item['active_from'] !== item['active_to']">
														{{ item['active_from'] | date("DD.MM") }}-{{ item['active_to'] | date("DD.MM") }}
													</template>
													<template v-else>
														{{ item['active_from'] | date("DD.MM") }}
													</template>
												</div>
											</td>
											<td>{{ item['name'] }}</td>
											<td>{{ item['discipline'] }}</td>
											<td>{{ item['country'] }}</td>
											<td>{{ item['city'] }}</td>
											<td>
												<div class="calendar-contacts">
													<p v-if="item['site'] !== ''"><a :href="item['site']" target="_blank">Сайт</a></p>
													<nuxt-link v-if="item['edit']" :to="'/personal/calendar/'+item['id']+'/'" class="edit-icon" title="Редактировать соревнование"></nuxt-link>
												</div>
											</td>
										</tr>
									</template>
								</tbody>
							</table>
						</div>
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

	import { calendarListQuery } from '~/utils/query.gql'

	const limit = 9999

	export default {
		name: "rating",
		middleware: 'authorization',
		components: {
			BottomResults,
			BottomImg,
			FutureEventsBottom,
			BottomRating,
			Menu
		},
		async asyncData (context)
		{
			try
			{
				let calendarListRequest = context.app.$apollo.query({
					query: calendarListQuery,
					variables: {
						limit: limit,
						page: 1,
						filter: {
							user: true,
						}
					},
					fetchPolicy: 'cache-first',
				})

				const [
					calendar,
				] = await Promise.all([
					calendarListRequest,
					context.store.dispatch('getPageInfo', context.route.path)
				])
				.then(([calendar]) =>
				{
					if (typeof calendar.errors !== 'undefined')
						throw new Error(calendar.errors[0].message)

					return [
						calendar.data['calendarList'],
					]
				})

				return {
					items: calendar['items'] || [],
					pagination: calendar['pagination'],
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
			return {}
		},
		computed: {
			_items ()
			{
				return this.items.map((item) =>
				{
					item['month'] = (new Date(item['active_from'])).getMonth() + 1

					return item
				})
			}
		}
	}
</script>