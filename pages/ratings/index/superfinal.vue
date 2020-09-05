<template>
	<div>
		<div class="shooter-rating-table">
			<div class="shooter-rating-table-header">
				<div class="table-results-text">
					{{ pagination.total }} {{ pagination.total | morph('стрелок', 'стрелка', 'стрелков') }}
				</div>
				<div class="rating-rules-link">
					<nuxt-link :to="{name: 'ratings-rules', params: { rules:'rules_superfinal' } }">Условия отбора в
						Суперфинал
					</nuxt-link>
				</div>
			</div>
			<div class="responsive-table-container">
				<table>
				<thead>
					<tr>
						<th class="shooter-place-col">№</th>
						<th>ФИО</th>
						<th>Категория</th>
						<th>Турниры</th>
						<th>Сумма лучших баллов</th>
					</tr>
				</thead>
				<tbody class="table-group" v-for="(shooter, index) in shooters" :class="{'active': actGroup === index}">
					<tr class="shooter-row" @click="toggleGroup(index)">
						<td class="shooter-place-col">
							<div class="table-hidden-title">№</div>
							<div class="shooter-place">
								<span class="shooter-place-num">{{ shooter['place'] }}</span>
							</div>
						</td>
						<td>
							<div class="table-hidden-title">ФИО</div>
							{{ shooter['name'] }}
						</td>
						<td>
							<div class="table-hidden-title">Категория</div>
							{{ shooter['group'] }}
						</td>
						<td>
							<div class="table-hidden-title">Турниры</div>
							{{ shooter['competitions'].length }}
						</td>
						<td>
							<div class="table-hidden-title">Рейтинг</div>
							{{ shooter['rating'].toFixed(2) }}
						</td>
					</tr>
					<tr class="hidden-row" v-for="item in shooter['competitions']">
						<td colspan="3">
							<div class="hidden-row-tournament-info">
								<div class="shooter-tournament-date">{{ item['date'] | date('DD.MM.YY') }}</div>
								<div class="shooter-tournament-title">{{ item['title'] }}</div>
							</div>
						</td>
						<td><span>{{ item['targets'] }}</span></td>
						<td><span>{{ item['or'].toFixed(2) }}</span></td>
					</tr>
				</tbody>
				<tbody v-if="shooters.length === 0">
					<tr>
						<td colspan="5">Нет результатов</td>
					</tr>
				</tbody>
			</table>
			</div>
		</div>

		<Pagination :data="pagination" @change="load"></Pagination>
	</div>
</template>

<script>
	import Pagination from '~/components/pagination.vue'
	import gql from 'graphql-tag';

	const ratingsQuery = gql`query ($page: Int, $limit: Int) {
		ratings: ratingsSuperfinal (page: $page, limit: $limit) {
			items {
				place
				name
				rating
				group
				competitions
			}
			pagination {
				total
				limit
				page
			}
		}
	}`;

	const limit = 25;

	export default {
		name: 'superfinal',
		components: {
			Pagination
		},
		async asyncData (context)
		{
			try
			{
				let ratingsRequest = context.app.$apollo.query({
					query: ratingsQuery,
					variables: {
						limit: limit,
						page: 1,
					},
					fetchPolicy: 'cache-first',
				});

				const ratings = await Promise.all([
					ratingsRequest,
					context.store.dispatch('getPageInfo')
				])
				.then(([ratings]) =>
				{
					if (typeof ratings.errors !== 'undefined')
						throw new Error(ratings.errors[0].message);

					return ratings.data
				})

				return {
					shooters: ratings['ratings']['items'],
					pagination: ratings['ratings']['pagination'],
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
				actGroup: null,
			}
		},
		methods: {
			toggleGroup (groupInd) {
				this.actGroup = this.actGroup === groupInd ? null: groupInd;
			},
			async load (page)
			{
				page = page || 1

				if (page < 1)
					page = 1

				try
				{
					const ratings = await this.$apollo.query({
						query: ratingsQuery,
						variables: {
							page: page,
							limit: limit
						},
						fetchPolicy: 'cache-first',
					})
					.then ((result) =>
					{
						if (typeof result.errors !== 'undefined')
							throw new Error(result.errors[0].message)

						return result.data['ratings']
					})

					this.shooters = ratings['items']
					this.pagination = ratings['pagination']
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