<template>
	<div class="page-content main-page-content">

		<Slider/>


		<FutureEventsBottom/>

		<section class="blue-img-section">
			<div class="container">
				<BottomResults/>
				<BottomRating/>
			</div>
		</section>

		<Winners/>
		<BottomImg/>
	</div>
</template>

<script>
	import BottomResults from '~/components/resultsBottom.vue'
	import BottomRating from '~/components/ratingBottom.vue'
	import BottomImg from '~/components/bottomImg.vue'
	import FutureEventsBottom from '~/components/futureEventsBottom.vue'
	import Winners from '~/components/winners.vue'
	import Slider from '~/components/index/slider.vue'

	export default {
		components: {
			BottomResults,
			BottomImg,
			FutureEventsBottom,
			BottomRating,
			Winners,
			Slider,
		},
		async asyncData (context)
		{
			try
			{
				await Promise.all([
					context.store.dispatch('getWinners'),
					context.store.dispatch('getSlider'),
					context.store.dispatch('getPageInfo')
				])
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