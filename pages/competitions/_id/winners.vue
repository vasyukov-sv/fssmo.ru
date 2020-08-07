<template>
	<div class="page-section-content">
		<div class="competition-winners">
			<div class="final-scan-top event-gallery-container" v-if="competition['protocols'] && competition['protocols'].length > 0">
				<button class="btn btn-gold" @click="protocolPopup = 0">Протокол финала</button>
				<client-only>
					<vue-gallery :images="competition['protocols']" :index="protocolPopup" @close="protocolPopup = null"></vue-gallery>
				</client-only>
			</div>
			<div class="row">
				<div class="competition-winners-col top-winner-col" v-if="winner">
					<div class="competition-winners-panel winner-panel">
						<div v-if="winner['image']" class="competition-winner-img" :style="{backgroundImage: 'url('+winner['image']+')'}">
							<img :src="winner['image']" alt="" />
						</div>
						<div class="competition-winners-panel-info">
							<div class="competition-winner-result">
								<div class="competition-winner-result-data">
									<div class="tournament-winner-score-progress">
										<div class="tournament-winner-score-text"><span>{{ winner['result'] }}</span>/{{ winner['result_max'] }}</div>
										<client-only>
											<svg-progress-bar :value="winner['result']" :options="progressOptions"></svg-progress-bar>
										</client-only>
									</div>
								</div>
							</div>
							<div class="competition-winner-info">
								<div class="competition-winner-name">{{ winner['name'] }} {{ winner['last_name'] }}</div>
								<div class="competition-winner-club">{{ winner['digit'] }}, {{ winner['club'] }}</div>
								<div class="competition-winner-text">
									{{ winner['description'] }}
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="competition-winners-col" v-for="(group, g) in groups">
					<div class="competition-winners-panel" :class="{'no-img': !group['image']}">
						<div v-if="group['image']" class="competition-winner-img" :style="{backgroundImage: 'url('+group['image']+')'}">
							<img :src="group['image']" alt="" />
						</div>
						<div class="competition-winners-panel-info">
							<div class="winner-panel-title">Категория {{ g }}</div>

							<table>
								<tbody>
									<tr v-for="member in group['members']">
										<td>{{ member['place'] }}</td>
										<td>{{ member['digit'] }}</td>
										<td>{{ member['name'] }}</td>
										<td>{{ member['club'] }}</td>
									</tr>
								</tbody>
							</table>

						</div>
					</div>
				</div>

				<div class="competition-winners-col" v-if="commands.length">
					<div class="competition-winners-panel">
						<div class="competition-winners-panel-info">
							<div class="winner-panel-title">Командное первенство</div>

							<div v-for="(command, i) in commands" class="winner-team">
								<div class="winner-team-title">
									<div class="winner-team-place">{{ i + 1 }}</div>
									{{ command['command'] }}
								</div>
								<div class="team-members-list">
									<div v-for="member in command['participants']" class="team-member">
										{{ member['name'] }}
									</div>
								</div>

							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
	import gql from 'graphql-tag';

	const winnersObjectQuery = gql`query ($id: String!) {
		results: competitionWinners (competition: $id)
	}`;

	const commandsListQuery = gql`query ($id: String!) {
		results: competitionCommandsResult (competition: $id) {
			command
			participants {
				name
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
				let winnersObjectRequest = context.app.$apollo.query({
					query: winnersObjectQuery,
					variables: {
						id: context.params.id,
					},
					fetchPolicy: 'cache-first'
				})

				let commandsListRequest = context.app.$apollo.query({
					query: commandsListQuery,
					variables: {
						id: context.params.id,
					},
					fetchPolicy: 'cache-first'
				})

				const [result1, result2] = await Promise.all([
					winnersObjectRequest,
					commandsListRequest
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
					winner: result1['winner'],
					groups: result1['groups'],
					commands: result2,
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
				progressOptions: {
					radius: 55,
					circleWidth: 4,
					pathColors: ['#d5d5d5', '#fa965c'],
					maxValue: 100
				},
				protocolPopup: null,
			}
		},
		created ()
		{
			if (this.winner)
				this.progressOptions.maxValue = this.winner['result_max'] || 100
		}
	}
</script>