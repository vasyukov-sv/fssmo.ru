<template>
	<div class="page-content-header" :class="headerClass">
		<section class="container">
			<breadcrumbs></breadcrumbs>
			<slot name="beforeTitle"></slot>
			<h1 v-if="title.length">{{ title }}</h1>
			<slot></slot>
		</section>
	</div>
</template>

<script>
	import Breadcrumbs from '~/components/breadcrumbs.vue'

	export default {
		name: "headerContent",
		props: {
			headerClass: {
				default: '',
				type: String
			},
		},
		data () {
			return {
				title: ''
			}
		},
		components: {
			Breadcrumbs
		},
		created ()
		{
			if (this.$store.state.page)
				this.title = this.$store.state.page.title;
		}
	}
</script>