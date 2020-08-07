<template>
	<section class="bottom-img-section">
		<div class="container">
			<div class="bottom-img-container" style="background-image: url('/images/bottom-img.jpg')">
				<div class="bottom-img-caption" v-editor:block="[{code: 'ENTER_BLOCK_TITLE', name: 'Заголовок', type: 'TEXT'}, {code: 'ENTER_BLOCK_DESC', name: 'Текст', type: 'HTML'}]">
					<div class="bottom-img-title">{{ $store.state.area['ENTER_BLOCK_TITLE'] }}</div>
					<div class="bottom-img-text" v-html="$store.state.area['ENTER_BLOCK_DESC']"></div>
				</div>
				<div class="bottom-img-btn">
					<nuxt-link to="/enter/" class="btn btn-gold">Присоединиться</nuxt-link>
				</div>
			</div>
		</div>
	</section>
</template>

<script>

	export default {
		data () {
			return {}
		}
	}
</script>