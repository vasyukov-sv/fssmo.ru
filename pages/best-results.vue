<template>
	<div class="page-content">
		<HeaderContent class="page-content-header-dark">
			<div class="best-result-search-top">
				<div class="best-result-search-field">
					<input type="text" placeholder="Фамилия стрелка" v-model="name" @keydown.enter="search">
					<button type="submit" class="btn btn-gold btn-small" :disabled="name.length < 3" @click.prevent="search">Искать</button>
				</div>
			</div>
		</HeaderContent>
		<section v-if="results !== null" class="container">
			<div class="best-results-table">
				<div class="best-results-table-text">По запросу найдено {{ results.length }} {{ results | morph('человек', 'человека', 'человек') }}</div>
				<div class="responsive-table-container">
					<table>
						<thead>
							<tr>
								<th>Дисциплина</th>
								<th>Мишени</th>
								<th>результат</th>
								<th>дата</th>
								<th>соревнование</th>
								<th>Разряд</th>
							</tr>
						</thead>
						<tbody>
							<template v-for="data in results">
								<tr class="person-name-row">
									<td colspan="6">
										<span class="table-person-name">{{ data['shooter']['last_name'] }} {{ data['shooter']['name'] }} {{ data['shooter']['second_name'] }}</span>
									</td>
								</tr>
								<tr v-for="competition in data['competitions']">
									<td>{{ competition['discipline'] }}</td>
									<td>{{ competition['targets'] }}</td>
									<td>{{ competition['summ'] }}</td>
									<td><span class="date">{{ competition['date'] | date('DD.MM.YYYY') }}</span></td>
									<td>{{ competition['title'] }}</td>
									<td>{{ competition['digit'] }}</td>
								</tr>
							</template>
						</tbody>
					</table>
				</div>
			</div>
		</section>
		<section class="blue-img-section">
			<div class="container">
				<BottomResults></BottomResults>
				<BottomRatingList></BottomRatingList>
			</div>
		</section>

		<FutureEventsBottom/>
		<BottomImg/>
	</div>
</template>

<script>
	import BottomResults from '~/components/resultsBottom.vue'
	import BottomRatingList from '~/components/ratingBottomList.vue'
	import BottomImg from '~/components/bottomImg.vue'
	import FutureEventsBottom from '~/components/futureEventsBottom.vue'

	import gql from 'graphql-tag';

	const searchQuery = gql`query ($name: String!) {
		results: bestResults (name: $name)
	}`;

	export default {
		components: {
			BottomRatingList,
			BottomResults,
			BottomImg,
			FutureEventsBottom,
		},
		async asyncData (context) {
			await context.store.dispatch('getPageInfo')
		},
		data () {
			return {
				name: '',
				results: null,
			}
		},
		created () {
			this.name = this.$route.query.name || ''
		},
		mounted () {
			this.search()
		},
		methods: {
			async search ()
			{
				if (this.name.length < 3)
					return

				try
				{
					const results = await this.$apollo.query({
						query: searchQuery,
						variables: {
							name: this.name,
						},
					})
					.then((result) =>
					{
						if (typeof result.errors !== 'undefined')
							throw new Error(result.errors[0].message)

						return result.data['results']
					})

					this.results = results || []
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