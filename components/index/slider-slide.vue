<template>
	<div class="swiper-slide main-slide">
		<div class="container">
			<div class="main-slide-container">
				<div v-if="slide['image']" class="main-slide-img swiper-lazy" :style="{'background-image': index === 0 ? 'url('+slide['image']+')' : 'transparent'}" :data-background="slide['image']">
					<div class="slide-round-1"></div>
					<div class="slide-round-2"></div>
				</div>
				<div class="main-slide-content">
					<div class="main-slide-content-container">
						<div v-if="slide['date_from']" class="main-slide-top">
							<span class="main-slide-date" v-if="slide['date_from']">{{ date }}</span>
						</div>
						<div class="main-slide-title">
							{{ slide['title'] }}
						</div>
						<div class="main-slide-announce" v-if="slide['description']" v-html="slide['description']"></div>
						<div class="main-slide-btn" v-if="slide['button_url']">
							<nuxt-link :to="slide['button_url']" class="btn" :class="{'btn-white-border': index % 2 === 0, 'btn-gold': index % 2 !== 0 }">{{ slide['button_text'] }}</nuxt-link>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
	import moment from 'moment';

	export default {
		name: "slider-slide",
		props: {
			slide: {
				type: Object
			},
			index: {
				type: Number,
				default: 0
			},
		},
		computed: {
			date ()
			{
				if (!this.slide['date_from'])
					return null;

				let from = moment(this.slide['date_from']);
				let to = moment(this.slide['date_to']);

				if (from.isSame(to))
					return moment(from).format('DD MMMM YYYY');
				else
					return moment(from).format('DD')+'-'+moment(to).format('DD MMMM YYYY');
			},
		}
	}
</script>