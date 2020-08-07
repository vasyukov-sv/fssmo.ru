<template>
	<div class="page-content">
		<HeaderContent></HeaderContent>
		<section class="container" v-editor:element="{iblock: 2, id: $store.state.page.id}">
			<div v-html="text" class="text-editable"></div>
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

	export default {
		components: {
			BottomResults,
			BottomRating,
			BottomImg,
			FutureEventsBottom,
		},
		async asyncData (context) {
			await context.store.dispatch('getPageInfo')
		},
		computed: {
			text () {
				return this.$store.state.page.text || ''
			},
		}
	}
</script>