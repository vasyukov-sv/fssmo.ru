<template>
	<div class="page-content">
		<HeaderContent></HeaderContent>
		<section class="container">
			<nav class="page-list-nav">
				<ul>
					<li><nuxt-link to="/ratings/sporting/" :class="{active: $route.path === '/ratings/'}">Спортинг</nuxt-link></li>
					<li><nuxt-link to="/ratings/sportingcompact/">Спортинг-компакт</nuxt-link></li>
					<li><nuxt-link to="/ratings/sportingdoublets/">Спортинг-дуплеты</nuxt-link></li>
					<li><nuxt-link to="/ratings/sportrap/">Спортрап</nuxt-link></li>
					<li><nuxt-link to="/ratings/superfinal/">Зачет суперфинала</nuxt-link></li>
				</ul>
			</nav>

			<nuxt-child :key="$route.name"></nuxt-child>
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
		name: 'ratings',
		components: {
			BottomResults,
			BottomRating,
			BottomImg,
			FutureEventsBottom,
		},
		data () {
			return {}
		},
	}
</script>