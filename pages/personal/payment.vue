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
						<div v-if="$route.query.error" class="payment-info payment-error">
							Во время оплаты произошла ошибка: {{ $route.query.error }}
						</div>
						<div v-else class="payment-info payment-success">
							Ваш платёж выполнен успешно
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
	import gql from 'graphql-tag';

	export default {
		name: "payment",
		middleware: 'authorization',
		components: {
			BottomResults,
			BottomImg,
			FutureEventsBottom,
			BottomRating,
			Menu,
		},
		async asyncData (context) {
			await context.store.dispatch('getPageInfo')
		},
		data () {
			return {
				timer: null,
			}
		},
		mounted ()
		{
			this.time = setTimeout(() => {
				this.$router.push('/personal/competitions/')
			}, 5000)
		},
		beforeDestroy () {
			clearTimeout(this.time)
		}
	}
</script>