<template>
	<div class="page-content">
		<HeaderContent></HeaderContent>
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
						<div class="user-competition-list">
							<div class="info-message">
								При отмене участия на в соревновании, стартовый взнос на которое был оплачен онлайн, денежные средства будут зачислены на ваш виртуальных счёт, с которого можно будет оплатить следующие соревнования<br>
								По всем вопросам можно обратиться к администрации ФССМО <a href="mailto:fssmo@mail.ru">fssmo@mail.ru</a>.
							</div>
							<div class="competition-results-list-row">
								<EventItem v-for="item in competitions" :key="item['id']" :item="item"/>
							</div>
							<div v-if="competitions.length === 0">
								Вы не зарегистрированы на соревнования
							</div>
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
	import EventItem from '~/components/personal/eventItem.vue'
	import gql from 'graphql-tag'

	const userCompetitionsQuery = gql`query {
		items: currentUserCompetitions
	}`

	export default {
		name: "competitions",
		middleware: 'authorization',
		components: {
			BottomResults,
			BottomImg,
			FutureEventsBottom,
			BottomRating,
			Menu,
			EventItem,
		},
		async asyncData (context)
		{
			try
			{
				let userCompetitions = context.app.$apollo.query({
					query: userCompetitionsQuery
				})

				let competitions = await Promise.all([
					userCompetitions,
					context.store.dispatch('getPageInfo')
				])
				.then(([result]) =>
				{
					if (typeof result.errors !== 'undefined')
						throw new Error(result.errors[0].message)

					return result.data['items']
				})

				return {
					competitions: competitions
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
	}
</script>
