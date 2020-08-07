<template>
	<nuxt-link :to="item['url']" class="news-panel" :class="{'news-competition': item['competition']}">
		<div v-if="item['image']" class="news-preview-img" :style="{backgroundImage: 'url('+item['image']+')'}"></div>
		<div class="news-panel-caption">
			<div class="news-panel-content">
				<div class="news-date">{{ item['date'] | date('DD MMMM YYYY') }}</div>
				<div class="news-title">{{ item['title'] }}</div>
				<div class="news-announce" v-html="item['preview']"></div>

				<div v-if="item['discipline']">{{ item['discipline'] }}</div>
				<div v-if="item['competition']">
					<template v-if="item['competition']['url'].indexOf('http') < 0">
						<nuxt-link v-if="item['competition']['registration']" :to="item['competition']['url']+'registration/'" class="btn btn-gold">зарегистрироваться</nuxt-link>
						<nuxt-link v-else :to="item['competition']['url']" class="btn btn-white-border">о соревновании</nuxt-link>
					</template>
					<a v-else :href="item['competition']['url']" class="btn btn-gold">зарегистрироваться</a>
				</div>
			</div>
		</div>
	</nuxt-link>
</template>

<script>
	export default {
		name: "newsItem",
		props: {
			item: Object
		},
	}
</script>