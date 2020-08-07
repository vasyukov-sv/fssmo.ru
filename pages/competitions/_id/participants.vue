<template>
	<div class="page-section-content">
		<div class="rating-search">
			<input type="text" v-model="name" placeholder="ФИО стрелка">
		</div>
		<div class="responsive-table-container">
			<table>
				<thead>
					<tr>
						<th>№</th>
						<th>
							<a href="#" @click.prevent="sortBy('name')" class="table-sort-link" :class="{active: sort === 'name', desc: order === 'desc'}">ФИО</a>
						</th>
						<th>
							<a href="#" @click.prevent="sortBy('city')" class="table-sort-link" :class="{active: sort === 'city', desc: order === 'desc'}">Город</a>
						</th>
						<th>
							<a href="#" @click.prevent="sortBy('club')" class="table-sort-link" :class="{active: sort === 'club', desc: order === 'desc'}">Клуб</a>
						</th>
						<th>
							<a href="#" @click.prevent="sortBy('digit')" class="table-sort-link" :class="{active: sort === 'digit', desc: order === 'desc'}">Разряд</a>
						</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="(participant, i) in items">
						<td><div class="table-hidden-title">№</div>{{ i + 1 }}</td>
						<td><div class="table-hidden-title">ФИО</div>{{ participant['name'] }}</td>
						<td><div class="table-hidden-title">Город</div>{{ participant['city'] }}</td>
						<td><div class="table-hidden-title">Клуб</div>{{ participant['club'] }}</td>
						<td><div class="table-hidden-title">Разряд</div><div class="rank">{{ participant['digit'] }}</div></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</template>

<script>
	import gql from 'graphql-tag';

	const participantListQuery = gql`query ($id: String!) {
		participants (competition: $id) {
			name
			city
			club
			digit
		}
	}`;

	export default {
		components: {
		},
		props: {
			competition: {
				type: Object
			}
		},
		async asyncData (context)
		{
			try
			{
				const participants = await context.app.$apollo.query({
					query: participantListQuery,
					variables: {
						id: context.params.id,
					},
					fetchPolicy: 'cache-first'
				})
				.then((result) =>
				{
					if (typeof result.errors !== 'undefined')
						throw new Error(result.errors[0].message)

					return result.data['participants']
				})

				return {
					participants: participants
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
				name: '',
				sort: '',
				order: 'asc'
			}
		},
		computed: {
			items ()
			{
				let items = this.participants.filter(item =>
				{
					if (this.name !== '')
						return item.name.toLowerCase().indexOf(this.name.toLowerCase()) >= 0;

					return true
				});

				if (this.sort !== '')
				{
					items.sort((a, b) =>
					{
						if (a[this.sort] !== '' && b[this.sort] === '')
							return -1;
						if (a[this.sort] === '' && b[this.sort] !== '')
							return 1;

						if (this.order === 'asc')
							return a[this.sort].toLowerCase() > b[this.sort].toLowerCase() ? 1 : -1;
						else
							return a[this.sort].toLowerCase() < b[this.sort].toLowerCase() ? 1 : -1;
					})
				}

				return items;
			}
		},
		head () {
			return {
				titleTemplate: '%s - Участники'
			}
		},
		methods: {
			sortBy (field)
			{
				if (field === this.sort)
					this.order = this.order === 'asc' ? 'desc' : 'asc';
				else
				{
					this.sort = field;
					this.order = 'asc';
				}
			}
		}
	}
</script>