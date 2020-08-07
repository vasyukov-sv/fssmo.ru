<template>
	<div class="page-wrap" :class="{preload: !loader}">
		<div class="page-wrap-content">
			<PageHeader/>
			<Nuxt/>
		</div>
		<PageFooter/>

		<client-only>
			<dialogs-wrapper transition-name="dialog-fade"/>
		</client-only>
	</div>
</template>

<script>
	import PageHeader from '~/components/header.vue'
	import PageFooter from '~/components/footer.vue'

	export default {
		name: 'default',
		components: {
			PageHeader,
			PageFooter
		},
		data () {
			return {
				loader: false
			}
		},
		mounted () {
			this.loader = true
			this.$bus.$on('logout', this.onLogout)
		},
		beforeDestroy () {
			this.$bus.$off('logout', this.onLogout)
		},
		head () {
			return this.$store.getters.metaTags
		},
		methods: {
			onLogout ()
			{
				if (this.$route.path.indexOf('/personal/') > -1)
					this.$router.push('/')
			},
		}
	}
</script>