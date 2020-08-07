<template>
	<ul class="breadcrumb">
		<li v-for="item in items">
			<nuxt-link :to="item['url']">{{ item['title'] }}</nuxt-link>
		</li>
	</ul>
</template>

<script>
	export default {
		name: "breadcrumbs",
		data () {
			return {
				items: []
			}
		},
		created ()
		{
			if (this.$store.state['page'])
				this.items = this.$store.state['page']['breadcrumbs'];
		}
	}
</script>