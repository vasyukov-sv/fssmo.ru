<template>
	<div class="photoes-list-col">
		<nuxt-link :to="item['url']" class="photo-list-item" :title="item['title']">
			<div class="photo-list-item-top">
				<div class="photo-list-item-info">
					<div class="photo-list-item-date">{{ item['date'] | date('DD MMMM YYYY') }}</div>
					<div class="photo-list-item-location">{{ item['location'] }}</div>
				</div>
				<div class="photo-list-item-title">{{ item['title'] }}</div>
			</div>

			<div class="photoes-list-gallery-grid" :class="['type-'+type]">
				<div class="gallery-grid-item" v-for="photo in item['photos']" :style="{backgroundImage: 'url('+photo['preview']+')'}"></div>
			</div>
		</nuxt-link>
	</div>
</template>

<script>
	export default {
		name: "photoItem",
		props: {
			item: {
				type: Object,
			}
		},
		computed: {
			type () {
				return Math.floor(Math.random() * (5 - 1) + 1)
			}
		}
	}
</script>