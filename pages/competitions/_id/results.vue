<template>
	<div class="page-section-content">
		<div class="competition-detail-results">
			<div class="competition-detail-results-top">
				<div class="rating-search">
					<input type="text" v-model="name" placeholder="ФИО стрелка">
				</div>
				<div class="competition-broadcast">
					<div class="competition-broadcast-title">Трансляция результатов:</div>
					<a :href="'/tv/?id='+competition['id']+'&cols=2'" class="broadcast-item broadcast-type-1" target="_blank"></a>
					<a :href="'/tv/?id='+competition['id']+'&cols=3'" class="broadcast-item broadcast-type-2" target="_blank"></a>
					<a :href="'/tv/commands/?id='+competition['id']" class="broadcast-item broadcast-type-3" target="_blank"></a>
					<a :href="'/tv/slideshow/?id='+competition['id']" class="broadcast-item broadcast-type-4" target="_blank"></a>
				</div>
			</div>
			<div v-if="Object.keys(groups).length > 1" class="groups-filter">
				<div class="groups-filter-title">Категории</div>
				<div class="groups-filter-params">
					<div class="groups-filter-list">
						<button v-for="(members, group) in groups" type="button" class="btn btn-blue-border btn-small" :class="{active: filter[group]}" @click.prevent="filterToggle(group)">{{ group }}</button>
					</div>
					<div v-if="resultsCom.length" class="groups-filter-teams">
						<button type="button" class="btn btn-blue-border btn-small" :class="{active: filter['command']}" @click.prevent="filterToggle('command')">командный зачет</button>
					</div>
				</div>
			</div>
			<div class="competition-result-section groups-result-section" v-for="(members, group) in groups" v-if="!isFiltered || filter[group]">
				<h4 v-if="group !== ''">{{ group }}</h4>
				<div class="responsive-table-container">
					<table>
					<thead>
						<tr>
							<th rowspan="2">Место</th>
							<th rowspan="2">№</th>
							<th rowspan="2">ФИО</th>
							<th rowspan="2">Разряд</th>
							<th :colspan="competition['stands']" class="stands-title-col">Площадки</th>
							<th rowspan="2">Сумма</th>
							<th rowspan="2">Вып. норма</th>
							<th rowspan="2">Страна</th>
							<th rowspan="2">Город</th>
							<th rowspan="2">Клуб</th>
						</tr>
						<tr>
							<th v-for="i in competition['stands']" class="stands-col">{{ i }}</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="member in members">
							<td>{{ member['place'] }}</td>
							<td>{{ member['number'] }}</td>
							<td>{{ member['name'] }}</td>
							<td>{{ member['digit'] }}</td>

							<td v-for="i in competition['stands']" class="stands-col">
								<template v-if="member['stands'][i] !== undefined">
									{{ member['stands'][i] }}
								</template>
								<template v-else>&ndash;</template>
							</td>

							<td>{{ member['summ'] }}</td>
							<td>{{ member['digitNew'] !== '' ? member['digitNew'] : '-' }}</td>
							<td>{{ member['country'] }}</td>
							<td>{{ member['city'] }}</td>
							<td>{{ member['club'] }}</td>
						</tr>
					</tbody>
				</table>
				</div>
			</div>
		</div>
		<div class="competition-result-section groups-result-section teams-result" v-if="commands.length && (!isFiltered || filter['command'])">
			<h4>Командный зачет</h4>
			<div class="responsive-table-container">
				<table>
				<thead>
					<tr>
						<th rowspan="2">Место</th>
						<th rowspan="2">№</th>
						<th rowspan="2">ФИО</th>
						<th rowspan="2">Разряд</th>
						<th :colspan="competition['stands']" class="stands-title-col">Площадки</th>
						<th rowspan="2">Сумма</th>
						<th rowspan="2">Клуб</th>
						<th rowspan="2">Итог</th>
					</tr>
					<tr>
						<th v-for="i in competition['stands']" class="stands-col">{{ i }}</th>
					</tr>
				</thead>
				<template v-for="(command, i) in commands">
					<tbody>
						<tr class="team-title-row">
							<td :colspan="8 + competition['stands']">
								<div class="team-title">{{ command['command'] }}</div>
							</td>
						</tr>
						<tr v-for="(member, index) in command['participants']">
							<td v-if="index === 0" :rowspan="command['participants'].length" class="team-total-col">{{ i + 1 }}</td>
							<td class="member-name-col">{{ member['number'] }}</td>
							<td>{{ member['name'] }}</td>
							<td>{{ member['digit'] }}</td>

							<td v-for="i in competition['stands']" class="stands-col">
								<template v-if="member['stands'][i] !== undefined">
									{{ member['stands'][i] }}
								</template>
								<template v-else>&ndash;</template>
							</td>

							<td>{{ member['summ'] }}</td>
							<td>{{ member['club'] }}</td>
							<td v-if="index === 0" :rowspan="command['participants'].length" class="team-total-col">{{ command['summ'] }}</td>
						</tr>
					</tbody>
				</template>
			</table>
			</div>
		</div>
	</div>
</template>

<script>
	import gql from 'graphql-tag';

	const resultListQuery = gql`query ($id: String!) {
		results: competitionResults (competition: $id) {
			place
			group
			number
			name
			summ
			country
			city
			club
			digit
			digitNew
			stands
		}
	}`;

	const commandResultListQuery = gql`query ($id: String!) {
		results: competitionCommandsResult (competition: $id) {
			command
			summ
			participants {
				number
				name
				summ
				club
				digit
				stands
			}
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
				let resultListRequest = context.app.$apollo.query({
					query: resultListQuery,
					variables: {
						id: context.params.id,
					},
					fetchPolicy: 'cache-first'
				})

				let commandResultListRequest = context.app.$apollo.query({
					query: commandResultListQuery,
					variables: {
						id: context.params.id,
					},
					fetchPolicy: 'cache-first'
				})

				const [result1, result2] = await Promise.all([
					resultListRequest,
					commandResultListRequest
				])
				.then(([result1, result2]) =>
				{
					if (typeof result1.errors !== 'undefined')
						throw new Error(result1.errors[0].message)

					if (typeof result2.errors !== 'undefined')
						throw new Error(result2.errors[0].message)

					return [result1['data']['results'], result2['data']['results']]
				})

				return {
					resultsInd: result1,
					resultsCom: result2,
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
				order: 'asc',
				filter: {}
			}
		},
		head () {
			return {
				titleTemplate: '%s - Результаты'
			}
		},
		computed: {
			groups ()
			{
				let groups = {};

				this.resultsInd.forEach((item) =>
				{
					if (this.name !== '' && item['name'].toLowerCase().indexOf(this.name.toLowerCase()) === -1)
						return;

					if (item['group'] === null)
						item['group'] = '';

					if (groups[item['group']] === undefined)
						groups[item['group']] = [];

					groups[item['group']].push(item);
				});

				return groups;
			},
			commands ()
			{
				if (this.name === '')
					return this.resultsCom

				return this.resultsCom.filter((command) =>
				{
					command['participants'] = command['participants'].filter((item) => {
						return item['name'].toLowerCase().indexOf(this.name.toLowerCase()) > -1
					})

					return command['participants'].length > 0
				})
			},
			isFiltered ()
			{
				let result = false;

				for (let i in this.filter)
				{
					if (this.filter.hasOwnProperty(i) && this.filter[i] === true)
						result = this.filter[i];
				}

				return result;
			}
		},
		methods: {
			filterToggle (group)
			{
				if (typeof this.filter[group] === 'undefined')
					this.$set(this.filter, group, true);
				else
					this.filter[group] = !this.filter[group];
			}
		},
	}
</script>