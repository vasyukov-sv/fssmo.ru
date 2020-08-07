<template>
	<div v-if="shedule" class="page-section-content">
		<SheduleDefaultView v-if="shedule['view'] === 'shedule'" :items="shedule['items']" :places="shedule['places']"/>
		<SheduleSnakeView v-if="shedule['view'] === 'snake'" :items="shedule['items']" :places="shedule['places']"/>
	</div>
</template>

<script>
	import SheduleDefaultView from '~/components/competitions/sheduleDefault.vue'
	import SheduleSnakeView from '~/components/competitions/sheduleSnake.vue'
	import gql from 'graphql-tag'

	const sheduleQuery = gql`query ($id: String!) {
		shedule: competitionShedule (competition: $id)
	}`;

	export default {
		components: {
			SheduleDefaultView,
			SheduleSnakeView,
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
				const shedule = await context.app.$apollo.query({
					query: sheduleQuery,
					variables: {
						id: context.params.id,
					},
					fetchPolicy: 'cache-first'
				})
				.then((result) =>
				{
					if (typeof result.errors !== 'undefined')
						throw new Error(result.errors[0].message)

					return result.data['shedule']
				})

				return {
					shedule: shedule
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
				titleTemplate: '%s - Расписание'
			}
		},
	}
</script>