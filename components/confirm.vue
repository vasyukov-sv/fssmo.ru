<template>
	<div class="dialog-box confirm-box">
		<div class="dialog-shadow"></div>
		<div class="dialog-content">
			<div v-if="title" class="dialog-title" v-html="title"></div>
			<div class="dialog-text" v-html="content"></div>
			<div class="dialog-buttons">
				<button v-for="button in buttons" type="button" class="btn" @click="handle(button.action)" v-html="button.text"></button>
			</div>
			<div class="clearfix"></div>
		</div>
	</div>
</template>

<script>
	export default {
		name: "confirm",
		props: {
			title: {
				type: String,
				default: '',
			},
			content: {
				type: String,
				default: '',
			},
			buttons: {
				type: Object,
				default: function () {
					return {
						ok: {
							text: 'ok',
						}
					}
				}
			}
		},
		beforeMount()
		{
			document.body.classList.add('loading')
			this.$bus.$on('closeModals', () => this.$close(false))
		},
		beforeDestroy ()
		{
			document.body.classList.remove('loading')
			this.$bus.$off('closeModals',() => this.$close(false))
		},
		methods: {
			handle (action)
			{
				if (typeof action === 'function')
					action()

				this.$close(false)
			}
		}
	}
</script>