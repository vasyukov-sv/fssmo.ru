<template>
	<div class="page-content">
		<HeaderContent/>
		<section class="container">
			<div class="competitions-calendar-container">
				<div class="competitions-calendar-filter">
					<div class="competitions-calendar-filter-item">
						<select v-model="filter.discipline">
							<option value="">Дисциплина</option>
							<option v-for="item in dictionary['disciplines']" :value="item['id']">{{ item['title'] }}</option>
						</select>
					</div>
					<div class="competitions-calendar-filter-item">
						<select v-model="filter.status">
							<option value="">Статус</option>
							<option v-for="item in dictionary['status']" :value="item['id']">{{ item['value'] }}</option>
						</select>
					</div>
					<div class="competitions-calendar-filter-item">
						<Multiselect
							v-model="filter.country"
							track-by="id"
							label="title"
							placeholder="Страна"
							:options="dictionary['country']"
							:searchable="true"
							:showLabels="false"
						>
							<template slot="noResult">
								Элементов не найдено
							</template>
						</Multiselect>
					</div>
					<div class="competitions-calendar-filter-item">
						<select v-model="filter.district" :disabled="!isRussia">
							<option value="">Федеральный округ</option>
							<option v-for="item in dictionary['districs']" :value="item['id']">{{ item['value'] }}</option>
						</select>
					</div>
					<div class="competitions-calendar-filter-item">
						<input v-if="!isRussia" type="text" v-model="filter.city" placeholder="Город">

						<Multiselect v-else
							v-model="filter.city"
							track-by="id"
							label="title"
							placeholder="Город"
							:options="city"
							:searchable="true"
							:showLabels="false"
							:loading="citySearching"
							@search-change="citySearch"
							:internal-search="false"
							:show-no-results="false"
							:clear-on-select="false"
						>
							<template slot="noOptions">
								Элементов не найдено
							</template>
						</Multiselect>
					</div>
					<div class="competitions-calendar-filter-item">
						<select v-model="filter.club">
							<option value="">Клуб</option>
							<option v-for="item in dictionary['clubs']" :value="item['id']">{{ item['title'] }}</option>
						</select>
					</div>
				</div>

				<nav class="page-list-nav page-list-nav-small">
					<ul>
						<li v-for="year in yearRange">
							<a href="#" @click.prevent="filter.year = year" :class="{'active': filter.year === year}">{{ year }}</a>
						</li>
					</ul>
				</nav>

				<div class="calendar-top-text">
					Добавить соревнование в Народный календарь может любой желающий через <nuxt-link to="/personal/calendar/">личный кабинет</nuxt-link>.
				</div>

				<div class="default-table">
					<table>
						<thead>
							<tr>
								<th>Даты проведения</th>
								<th>Название</th>
								<th>Дисциплина</th>
								<th>Мишеней</th>
								<th>Статус</th>
								<th>Страна</th>
								<th>Фед. округ</th>
								<th>Город</th>
								<th>Клуб</th>
								<th>Контакты</th>
							</tr>
						</thead>
						<tbody>
							<template v-for="(item, i) in _items">
								<tr v-if="i === 0 || _items[i - 1]['month'] !== item['month']" class="person-name-row">
									<td colspan="10"><span class="table-person-name">{{ item['active_from'] | date("MMMM") }}</span></td>
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
									<td>{{ item['targets'] || '' }}</td>
									<td>{{ item['status'] }}</td>
									<td>{{ item['country'] }}</td>
									<td>{{ item['district'] }}</td>
									<td>{{ item['city'] }}</td>
									<td>{{ item['club'] }}</td>
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

				<Pagination :data="pagination" @change="loadList"></Pagination>
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
	import Pagination from '~/components/pagination.vue'
	import Multiselect from 'vue-multiselect'

	import { getClubsQuery, disciplinesQuery, calendarFormQuery, locationsQuery, calendarListQuery } from '~/utils/query.gql'

	const limit = 9999

	export default {
		name: 'calendar',
		components: {
			BottomResults,
			BottomRating,
			BottomImg,
			FutureEventsBottom,
			Multiselect,
			Pagination,
		},
		async asyncData (context)
		{
			try
			{
				let clubsRequest = context.app.$apollo.query({
					query: getClubsQuery,
					fetchPolicy: 'cache-first',
				})

				let disciplinesRequest = context.app.$apollo.query({
					query: disciplinesQuery,
					fetchPolicy: 'cache-first',
				})

				let calendarFormRequest = context.app.$apollo.query({
					query: calendarFormQuery,
					fetchPolicy: 'cache-first',
				})

				let calendarListRequest = context.app.$apollo.query({
					query: calendarListQuery,
					variables: {
						limit: limit,
						page: 1,
					},
					fetchPolicy: 'cache-first',
				})

				const [
					clubs,
					disciplines,
					country,
					districs,
					status,
					calendar
				] = await Promise.all([
					clubsRequest,
					disciplinesRequest,
					calendarFormRequest,
					calendarListRequest,
					context.store.dispatch('getPageInfo', context.route.path)
				])
				.then(([clubs, disciplines, form, calendar]) =>
				{
					if (typeof clubs.errors !== 'undefined')
						throw new Error(clubs.errors[0].message)

					return [
						clubs.data['clubs'],
						disciplines.data['disciplines'],
						form.data['form']['country'],
						form.data['form']['districs'],
						form.data['form']['status'],
						calendar.data['calendarList'],
					]
				})

				return {
					dictionary: {
						clubs: clubs || [],
						disciplines: disciplines || [],
						country: country || [],
						districs: districs || [],
						status: status || [],
					},
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
			return {
				filter: {
					country: '',
					district: '',
					city: '',
					club: '',
					discipline: '',
					status: '',
					year: (new Date()).getFullYear(),
				},
				citySearching: false,
				city: [],
				items: [],

				pagination: {
					total: 500,
					limit: 10,
					page: 1
				},
			}
		},
		computed: {
			isRussia () {
				if (!this.filter.country)
					return false

				return this.dictionary.country.find((item) => {
					return this.filter.country['id'] === item['id'] && item['title'].toLowerCase().indexOf('россия') !== -1
				}) !== undefined
			},
			yearRange ()
			{
				let items = []

				for (let i = (new Date()).getFullYear() + 1; i >= 2008; i--)
					items.push(i)

				return items
			},
			_items ()
			{
				return this.items.map((item) =>
				{
					item['month'] = (new Date(item['active_from'])).getMonth() + 1

					return item
				})
			}
		},
		watch: {
			'filter.country' () {
				this.filter.city = ''
			},
			filter: {
				handler: function () {
					this.loadList()
				},
				deep: true
			}
		},
		methods: {
			async citySearch (query)
			{
				if (query.length <= 1)
					return

				this.citySearching = true

				try
				{
					this.city = await this.$apollo.query({
						query: locationsQuery,
						variables: {
							query: query,
						},
					})
					.then((result) =>
					{
						if (typeof result.errors !== 'undefined')
							throw new Error(result.errors[0].message)

						return result.data['locations']
					})

					this.citySearching = false
				}
				catch (e)
				{
					this.$modal.alert({
						title: 'Ошибка',
						content: e.message
					})
				}
			},
			async loadList (page)
			{
				page = page || 1

				if (page < 1)
					page = 1

				try
				{
					const calendar = await this.$apollo.query({
						query: calendarListQuery,
						variables: {
							page: page,
							limit: limit,
							filter: {
								country: this.filter.country ? this.filter.country['id'] : 0,
								district: this.filter.district,
								city: this.filter.city && typeof this.filter.city === 'object' ? this.filter.city['title'] : this.filter.city,
								club: this.filter.club,
								discipline: this.filter.discipline,
								status: this.filter.status,
								year: this.filter.year,
							}
						},
					})
					.then((result) =>
					{
						if (typeof result.errors !== 'undefined')
							throw new Error(result.errors[0].message)

						return result.data['calendarList']
					})

					this.items = calendar['items']
					this.pagination = calendar['pagination']
				}
				catch (e)
				{
					this.$modal.alert({
						title: 'Ошибка',
						content: e.message
					})
				}
			},
		},
	}
</script>