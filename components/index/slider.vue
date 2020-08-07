<template>
	<div v-if="items.length > 0" class="main-slider swiper-container">
		<div ref="slider" class="top-slider">
			<div class="swiper-wrapper">
				<Slide v-for="(slide, i) in items" :key="slide['id']" :slide="slide" :index="i"/>
			</div>
			<div class="slider-controls" ref="controls" data-screen="1">
				<div class="swiper-pagination main-pagination" slot="pagination"></div>
				<div class="swiper-navigation" slot="navigation">
					<div class="swiper-button-default button-default-next main-next" slot="button-next"></div>
					<div class="swiper-button-default button-default-prev main-prev" slot="button-prev"></div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
	import Slide from './slider-slide.vue'
	import { Swiper } from 'swiper/dist/js/swiper.esm'

	export default {
		name: "slider",
		components: {
			Slide,
		},
		data () {

			let _this = this;

			return {
				swiper: null,
				options: {
					loop: false,
					speed: 500,
					lazy: {
						loadPrevNext: true,
					},
					autoplay: {
						delay: 5000,
					},
					watchOverflow: true,
					autoHeight: true,
					pagination: {
						el: '.main-pagination',
						type: 'fraction',
						formatFractionCurrent: (number) => {
							return (("0" + number).slice(-2))
						},
						formatFractionTotal: (number) => {
							return (("0" + number).slice(-2))
						}
					},
					effect: 'fade',
					fadeEffect: {
						crossFade: false
					},
					navigation: {
						nextEl: '.main-next',
						prevEl: '.main-prev'
					},
					on: {
						slideChange () {
							_this.slideChange(this.activeIndex)
						}
					}
				},
			}
		},
		computed: {
			items () {
				return this.$store.state.slider || []
			},
		},
		methods: {
			slideChange (index)
			{
				if (!this.$refs['controls'])
					return

				this.$refs['controls'].setAttribute('data-screen', (index + 1) % 2)
				this.$emit('slideChange', index)
			}
		},
		mounted ()
		{
			try
			{
				this.swiper = new Swiper(
					this.$refs['slider'],
					this.options
				)
			}
			catch (e) {
				console.log(e)
			}
		},
		beforeDestroy ()
		{
			try
			{
				if (this.swiper)
					this.swiper.destroy()
			}
			catch (e) {
				console.log(e)
			}
		}
	}
</script>