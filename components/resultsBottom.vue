<template>
	<div v-if="results.length > 0" class="last-results-container">
		<div class="last-results-container-bck"></div>
		<div class="last-results-header">
			<h2 class="white">Свежие результаты</h2>
			<div class="slider-controls-default">
				<div class="swiper-navigation" slot="navigation">
					<div class="swiper-button-default button-default-prev results-prev" slot="button-prev"></div>
					<div class="swiper-button-default button-default-next results-next" slot="button-next"></div>
				</div>
			</div>
		</div>
		<div class="last-results-slider-container">
			<div ref="slider" class="results-slider">
				<div class="swiper-wrapper">
					<div v-for="(item, i) in results" :key="i" class="swiper-slide result-slide">
						<ResultItem :item="item"></ResultItem>
					</div>
				</div>
			</div>
		</div>
		<div class="loadmore-btn">
			<nuxt-link to="/results/" class="btn btn-gold">все результаты</nuxt-link>
		</div>
	</div>
</template>

<script>
	import ResultItem from '~/components/results/resultItem.vue'
	import { Swiper } from 'swiper/dist/js/swiper.esm'

	export default {
		components: {
			ResultItem
		},
		data () {
			return {
				sliderOptions: {
					loop: false,
					speed: 500,
					lazy: false,
					watchOverflow: true,
					spaceBetween: 15,
					slidesPerView: 3,
					navigation: {
						nextEl: '.results-next',
						prevEl: '.results-prev'
					},
					freeMode: true,
					freeModeSticky: true,
					breakpoints: {
						1000: {
							slidesPerView: 2
						},
						576: {
							slidesPerView: 1
						}
					}
				},
			}
		},
		computed: {
			results () {
				return this.$store.state['lastResults'] || []
			},
		},
		mounted ()
		{
			new Swiper(
				this.$refs['slider'],
				this.sliderOptions
			)
		}
	}
</script>