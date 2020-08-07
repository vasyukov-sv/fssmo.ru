<template>
	<div>
		<div class="rating-search">
			<input type="text" v-model="name" placeholder="ФИО стрелка">
		</div>
		<nav class="page-list-nav page-list-nav-small">
			<ul>
				<li><a href="#" @click.prevent="changeType('')" :class="{'active': type === ''}">Полный</a></li>
				<li><a href="#" @click.prevent="changeType('juniors')" :class="{'active': type === 'juniors'}">Юниоры</a></li>
				<li><a href="#" @click.prevent="changeType('womens')" :class="{'active': type === 'womens'}">Женщины</a></li>
				<li><a href="#" @click.prevent="changeType('veterans')" :class="{'active': type === 'veterans'}">Ветераны</a></li>
			</ul>
		</nav>
		<div class="shooter-rating-table">
			<div class="shooter-rating-table-header">
				<div class="table-results-text">
					{{ pagination.total }} {{ pagination.total | morph('стрелок', 'стрелка', 'стрелков') }}
				</div>
				<div class="rating-rules-link">
					<nuxt-link to="/ratings/rules/">Правила ведения рейтинга</nuxt-link>
				</div>
			</div>
			<RatingTable :sort="sort" :shooters="shooters" @sort="sortBy"/>
			<Pagination :data="pagination" @change="load"></Pagination>
		</div>
	</div>
</template>

<script>
	import RatingTable from '~/components/ratingTable.vue'
	import Pagination from '~/components/pagination.vue'
	// noinspection ES6CheckImport
	import { ratingsListQuery } from '~/utils/query.gql'

	const discipline = 1;
	const limit = 25;

	const ratingCodes = {
		'sportingcompact': 2,
		'sporting': 1,
		'sportingdoublets': 4,
		'sportrap': 9,
	};

	export default {
		name: "ratings-items",
		components: {
			Pagination,
			RatingTable,
		},
		async asyncData (context)
		{
			try
			{
				let disc = discipline;
				let code = context.route.path.replace(/^\//, '').split('/');

				if (typeof code[1] !== 'undefined')
				{
					if (typeof ratingCodes[code[1]] !== 'undefined')
						disc = ratingCodes[code[1]];
				}

				let ratingsRequest = context.app.$apollo.query({
					query: ratingsListQuery,
					variables: {
						filter: {
							discipline: disc,
							type: '',
							name: ''
						},
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
					discipline: disc,
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
				type: '',
				name: '',
				sort: {
					field: 'place',
					order: 'asc',
				}
			}
		},
		watch: {
			type () {
				this.load()
			},
			name () {
				this.load()
			}
		},
		methods: {
			changeType (typeId) {
				this.type = typeId
			},
			sortBy (field)
			{
				if (field === this.sort.field)
					this.sort.order = this.sort.order === 'asc' ? 'desc' : 'asc'
				else
				{
					this.sort.field = field
					this.sort.order = 'asc'
				}

				this.load()
			},
			load (page)
			{
				page = page || 1

				if (page < 1)
					page = 1

				this.$apollo.query({
					query: ratingsListQuery,
					variables: {
						filter: {
							discipline: this.discipline,
							type: this.type,
							name: this.name.length >= 3 ? this.name : '',
						},
						sort: this.sort,
						page: page,
						limit: limit
					},
				})
				.then ((result) => {
					this.shooters = result.data['ratings']['items']
					this.pagination = result.data['ratings']['pagination']
				})
			},
		}
	}
</script>