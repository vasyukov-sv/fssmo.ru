<template>
	<div>
		<iframe v-if="type === 'block'" style="width:100%;height:700px;" :src="'/bitrix/admin/olympia_visual.php?type='+type+'&field='+field+'&back_url='+back_url"></iframe>
	</div>
</template>

<script>
	export default {
		props: ['type', 'value'],
		data () {
			return {
				back_url: window.location.href
			}
		},
		computed: {
			field () {
				return encodeURI(JSON.stringify(this.value));
			}
		},
	}
</script>