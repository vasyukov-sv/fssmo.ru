<template>
	<div class="page-section-content">

		<div class="groups-list">
			<div class="row">
				<div class="group-col" v-for="group in groups">
					<div class="group-panel">
						<div class="group-title">{{ group['number'] }} Группа</div>
						<ul class="groups-members-list">
							<li v-for="participant in group['participants']" class="group-list-item">
								<div class="group-list-number">{{ participant['number'] }}</div>
								{{ participant['name'] }}
							</li>
						</ul>
					</div>
				</div>
			</div>
		</div>

	</div>
</template>

<script>
	import gql from 'graphql-tag';

	const groupListQuery = gql`query ($id: String!) {
		groups: competitionGroups (competition: $id) {
			number
			participants {
				number
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
				const groups = await context.app.$apollo.query({
					query: groupListQuery,
					variables: {
						id: context.params.id,
					},
					fetchPolicy: 'cache-first'
				})
				.then((result) =>
				{
					if (typeof result.errors !== 'undefined')
						throw new Error(result.errors[0].message)

					return result.data['groups']
				})

				return {
					groups: groups
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
		head () {
			return {
				titleTemplate: '%s - Группы'
			}
		},
	}
</script>