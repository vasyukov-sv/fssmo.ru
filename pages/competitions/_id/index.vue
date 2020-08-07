<template>
	<div class="page-section-content" v-html="competition['detail_text']"></div>
</template>

<script>
	export default {
		props: {
			competition: {
				type: Object
			}
		}
	}
</script>