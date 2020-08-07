<template>
	<div class="success-modal" v-html="text"></div>
</template>

<script>
	export default {
		name: "successModal",
		props: {
			text: {
				type: String,
				default: () => {
					return ''
				}
			},
			error: {
				type: Boolean,
				default: () => {
					return false
				}
			}
		}
	}
</script>